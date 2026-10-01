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
 * name ("mrs. hope" becomes "mrs. Lname"). A known name that is part of
 * someone else's full name - "Thomas Jefferson" when a classmate is called
 * Thomas - is left for the AI, which keeps historical figures.
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

    /** @var string[] Words that, before a last name, still mean "this is that person" ("Principal Smith"). */
    const LEADING_WORDS = [
        'mr', 'mrs', 'ms', 'miss', 'mx', 'dr', 'coach', 'sir', 'madam', 'mister', 'principal', 'teacher',
        'professor', 'prof', 'nurse', 'officer', 'deputy', 'pastor', 'reverend', 'rev', 'father', 'sister',
        'brother', 'aunt', 'uncle', 'grandma', 'grandpa', 'grandmother', 'grandfather', 'cousin', 'captain',
        'sergeant', 'judge', 'superintendent', 'dear', 'and', 'with', 'from', 'to', 'by', 'friend',
    ];

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
     * Whether a word is a known name or one of the placeholders.
     *
     * @param string $word
     * @param array $names
     * @return bool
     */
    protected static function is_known($word, array $names) {
        return $word === 'Fname' || $word === 'Lname' || isset($names[\core_text::strtolower($word)]);
    }

    /**
     * Every place a known name occurs in the text that should be treated as
     * that person's name, as [byte offset, byte length, title, placeholder].
     *
     * A match is skipped when:
     * - it starts with a lowercase letter and has no title before it
     *   ("I will see" - see the class comment);
     * - it is a first name followed, on the same line, by a capitalised word
     *   that is not a known name - that is someone else's full name, such as
     *   "Thomas Jefferson" when a classmate is called Thomas, and the AI
     *   decides whether to redact it;
     * - it is a last name preceded, on the same line, by a capitalised word
     *   that is not a known name or a title-like word - "Samuel Adams" when
     *   a classmate's surname is Adams.
     *
     * @param string $text
     * @param string $name
     * @param string $placeholder
     * @param array $names
     * @return array
     */
    protected static function matches($text, $name, $placeholder, array $names) {
        $found = [];
        if (!preg_match_all(self::pattern($name), $text, $all, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $found;
        }

        foreach ($all as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $title = (isset($m[1]) && $m[1][1] >= 0) ? $m[1][0] : '';
            $nameonly = substr($whole, strlen($title));
            $namestart = $start + strlen($title);
            $nameend = $start + strlen($whole);

            if ($title === '') {
                $first = \core_text::substr($nameonly, 0, 1);
                if ($first !== \core_text::strtoupper($first)) {
                    continue;
                }
            }

            if (
                $title === '' && $placeholder === 'Fname'
                && preg_match('/\G[ \t]+(\p{Lu}[\p{L}\'\-]*)/u', $text, $next, 0, $nameend)
                && !self::is_known($next[1], $names)
            ) {
                continue;
            }

            if (
                $title === '' && $placeholder === 'Lname'
                && preg_match('/(\p{Lu}[\p{L}\'\-]*)[ \t]+$/u', substr($text, 0, $namestart), $prev)
                && !self::is_known($prev[1], $names)
                && !in_array(\core_text::strtolower($prev[1]), self::LEADING_WORDS)
            ) {
                continue;
            }

            $found[] = [$namestart, strlen($nameonly), $nameonly];
        }
        return $found;
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
            // Replace from the end, so earlier offsets stay valid.
            foreach (array_reverse(self::matches($text, $name, $placeholder, $names)) as [$offset, $length]) {
                $text = substr_replace($text, $placeholder, $offset, $length);
            }
        }
        return $text;
    }

    /**
     * The known names still present in a piece of (supposedly redacted)
     * text, for spot-checking. Uses the same rules as apply().
     *
     * @param string $text
     * @param array $names As returned by for_course()
     * @return string[] The names found, as they appear in the text
     */
    public static function find($text, array $names) {
        $found = [];
        foreach ($names as $name => $placeholder) {
            foreach (self::matches($text, $name, $placeholder, $names) as [, , $match]) {
                $found[$match] = $match;
            }
        }
        return array_values($found);
    }

    /**
     * Replaces links to Google Docs and Google Drive with "[link]": a link
     * to a student's own document leads to their name. Other web addresses
     * (sources a student cites) are left alone.
     *
     * @param string $text
     * @return string
     */
    public static function redact_links($text) {
        return preg_replace('#https?://(?:docs|drive)\.google\.com/\S+#iu', '[link]', $text);
    }
}
