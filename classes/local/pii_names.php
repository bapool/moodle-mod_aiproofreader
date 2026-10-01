<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_aiproofreader\local;

/**
 * Exact, code-based redaction of the names Moodle already knows.
 *
 * The AI can only guess which words in a piece of writing are names, and it
 * misses some (a last name read as an ordinary word, a teacher written as
 * "Mr. Smith"). But every name that matters most - the student, their
 * classmates, and their teachers - is already in Moodle. This class collects
 * the names of everyone enrolled in the course (any role, active or not) and
 * replaces them with the same placeholders the AI uses: first, middle and
 * alternate names become "Fname", last names become "Lname".
 *
 * Matching is whole-word and ignores case, except that a match starting
 * with a lowercase letter is left alone, so a name that is also an ordinary
 * word ("Will", "May", "Hunter") isn't stripped out of normal sentences -
 * unless it follows a title such as "Mr." or "Mrs.", which always marks a
 * name ("mrs. hope" becomes "mrs. Lname").
 * The trade-off: a capitalized ordinary word at the start of a sentence that
 * happens to match someone's name is redacted too. Over-redacting is the
 * safer mistake for a public release. A name the student typed entirely in
 * lowercase is left for the AI pass to catch.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pii_names {
    /** @var int Names (or name parts) shorter than this are ignored - too likely to be ordinary words. */
    const MIN_LENGTH = 3;

    /** @var array Per-request cache of name lists, keyed by course id. */
    protected static $cache = [];

    /**
     * The known names for a course: every enrolled user's names, mapped to
     * the placeholder that replaces them, longest first so "De La Cruz" is
     * replaced before "Cruz".
     *
     * @param int $courseid
     * @return array name => placeholder ("Fname" or "Lname")
     */
    public static function for_course($courseid) {
        if (isset(self::$cache[$courseid])) {
            return self::$cache[$courseid];
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        $names = [];
        if ($context) {
            $fields = 'u.id, u.firstname, u.lastname, u.middlename, u.alternatename';
            $users = get_enrolled_users($context, '', 0, $fields, null, 0, 0, false);
            foreach ($users as $user) {
                // Last names first, so a name that is someone's first name and
                // someone else's last name is still replaced (as Lname).
                self::add($names, $user->lastname, 'Lname');
                foreach (['firstname', 'middlename', 'alternatename'] as $field) {
                    self::add($names, $user->$field ?? '', 'Fname');
                }
            }
        }

        uksort($names, function ($a, $b) {
            return \core_text::strlen($b) <=> \core_text::strlen($a);
        });

        self::$cache[$courseid] = $names;
        return $names;
    }

    /**
     * Adds a name, and each part of a multi-part name, to the list.
     *
     * @param array $names
     * @param string $name
     * @param string $placeholder
     */
    protected static function add(array &$names, $name, $placeholder) {
        $name = trim((string) $name);
        if ($name === '') {
            return;
        }

        $candidates = [$name];
        // Multi-part names ("Mary Ann", "Smith-Jones", "Van Buren"): also match each part on its own.
        $parts = preg_split('/[\s\-]+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) > 1) {
            $candidates = array_merge($candidates, $parts);
        }

        foreach ($candidates as $candidate) {
            if (\core_text::strlen($candidate) < self::MIN_LENGTH) {
                continue;
            }
            $key = \core_text::strtolower($candidate);
            if (!isset($names[$key])) {
                $names[$key] = $placeholder;
            }
        }
    }

    /** @var string Titles that mark the following word as a name, even when typed in lowercase ("mrs. hope"). */
    const TITLES = 'mr|mrs|ms|miss|mx|dr|coach|sir|madam';

    /**
     * The regular expression matching one name as a whole word. Group 1
     * captures a title just before the name, if there is one.
     *
     * @param string $name
     * @return string
     */
    protected static function pattern($name) {
        return '/(?<![\p{L}\p{N}])(?:((?:' . self::TITLES . ')\.?\s+))?'
            . '(?<![\p{L}\p{N}])' . preg_quote($name, '/') . '(?![\p{L}\p{N}])/iu';
    }

    /**
     * Whether a match should be treated as a name: it follows a title, or
     * it does not start with a lowercase letter (see the class comment).
     *
     * @param string $name The matched name, without any title
     * @param string $title The title before it, or ''
     * @return bool
     */
    protected static function looks_like_name($name, $title) {
        if ($title !== '') {
            return true;
        }
        $first = \core_text::substr($name, 0, 1);
        return $first === \core_text::strtoupper($first);
    }

    /**
     * Splits a match of pattern() into its title and name parts.
     *
     * @param array $m The match array
     * @return string[] [title, name]
     */
    protected static function split_match(array $m) {
        $title = $m[1] ?? '';
        return [$title, \core_text::substr($m[0], \core_text::strlen($title))];
    }

    /**
     * Replaces every known name in the text with its placeholder.
     *
     * @param string $text
     * @param array $names As returned by for_course()
     * @return string
     */
    public static function apply($text, array $names) {
        foreach ($names as $name => $placeholder) {
            $text = preg_replace_callback(
                self::pattern($name),
                function ($m) use ($placeholder) {
                    [$title, $name] = self::split_match($m);
                    return self::looks_like_name($name, $title) ? $title . $placeholder : $m[0];
                },
                $text
            );
        }
        return $text;
    }

    /**
     * The known names still present in a piece of (supposedly redacted)
     * text, for spot-checking.
     *
     * @param string $text
     * @param array $names As returned by for_course()
     * @return string[] The names found, as they appear in the text
     */
    public static function find($text, array $names) {
        $found = [];
        foreach ($names as $name => $placeholder) {
            if (preg_match_all(self::pattern($name), $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    [$title, $match] = self::split_match($m);
                    if (self::looks_like_name($match, $title)) {
                        $found[$match] = $match;
                    }
                }
            }
        }
        return array_values($found);
    }
}
