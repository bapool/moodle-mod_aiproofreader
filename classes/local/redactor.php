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
 * Redacts PII from one piece of student writing.
 *
 * Shared by the nightly task and the --test option of
 * cli/reset_redaction.php, so a test shows exactly what the task does.
 *
 * Steps for each text:
 * 1. In code: known names (everyone enrolled in the course, see
 *    {@see pii_names}) and links to Google Docs/Drive are replaced, so they
 *    are never sent to the AI.
 * 2. If nothing but placeholders is left (a draft that was only a link),
 *    that is the result - there is nothing for the AI to do.
 * 3. The AI (Moodle's AI subsystem) redacts everything else.
 * 4. For texts of MIN_CHECK_LENGTH characters or more, output far shorter or
 *    longer than the text sent is rejected - the AI summarised, refused or
 *    added commentary - and nothing is saved, so the nightly task retries it.
 *    Short texts are exempt: one redaction changes their length a lot, and
 *    a summary isn't a risk there.
 * 5. In code again: known names and links, in case the AI put any back.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class redactor {
    /** @var float Smallest acceptable length of the AI output, relative to the text sent. */
    const MIN_LENGTH_RATIO = 0.8;

    /** @var float Largest acceptable length of the AI output, relative to the text sent. */
    const MAX_LENGTH_RATIO = 1.25;

    /** @var int Texts shorter than this (in characters) skip the length check. */
    const MIN_CHECK_LENGTH = 500;

    /**
     * Redacts one text.
     *
     * @param string $text The student's original text
     * @param int $courseid The course the submission belongs to (for known names)
     * @param int $contextid Module context id
     * @param int $userid The student
     * @return array With keys success, redacted, prepared (text sent to the AI),
     *               raw (the AI's answer), ratio, count (placeholders), skippedai and error
     */
    public static function redact(string $text, int $courseid, int $contextid, int $userid): array {
        $result = [
            'success' => false, 'redacted' => '', 'prepared' => '', 'raw' => '',
            'ratio' => 0.0, 'count' => 0, 'skippedai' => false, 'error' => '',
        ];

        // Step 1: known names and Google links, in code.
        $knownnames = pii_names::for_course($courseid);
        $prepared = pii_names::redact_links(pii_names::apply($text, $knownnames));
        $result['prepared'] = $prepared;

        // Step 2: nothing left but placeholders - no AI needed.
        $remaining = trim(str_replace(['Fname', 'Lname', '[link]', '[email]', '[phone]', '[address]'], '', $prepared));
        if (!preg_match('/[\p{L}\p{N}]/u', $remaining)) {
            $result['skippedai'] = true;
            return self::finish($result, $prepared);
        }

        // Step 3: the AI.
        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $userid,
                prompttext: get_string('piiredactionprompt', 'aiproofreader', $prepared)
            );
            $response = \core\di::get(\core_ai\manager::class)->process_action($action);
        } catch (\Throwable $e) {
            $result['error'] = 'exception - ' . $e->getMessage();
            return $result;
        }
        if (!$response->get_success()) {
            $result['error'] = 'AI request unsuccessful - ' . $response->get_errormessage();
            return $result;
        }
        $raw = trim((string) ($response->get_response_data()['generatedcontent'] ?? ''));
        // Some models include their reasoning in <think> tags.
        $raw = trim(preg_replace('#<think>.*?</think>#is', '', $raw));
        $result['raw'] = $raw;
        if ($raw === '') {
            $result['error'] = 'AI returned empty content';
            return $result;
        }

        // Step 4: for longer texts, the answer must be about the same length as the text sent.
        $preparedlength = \core_text::strlen($prepared);
        $ratio = \core_text::strlen($raw) / max(1, $preparedlength);
        $result['ratio'] = $ratio;
        if (
            $preparedlength >= self::MIN_CHECK_LENGTH
            && ($ratio < self::MIN_LENGTH_RATIO || $ratio > self::MAX_LENGTH_RATIO)
        ) {
            $result['error'] = 'AI output length looks wrong (' . round($ratio, 2) . 'x the original)';
            return $result;
        }

        // Step 5: known names and links once more, in case the AI put any back.
        return self::finish($result, pii_names::redact_links(pii_names::apply($raw, $knownnames)));
    }

    /**
     * Marks a result successful and counts its placeholders.
     *
     * @param array $result
     * @param string $redacted
     * @return array
     */
    protected static function finish(array $result, string $redacted): array {
        $result['success'] = true;
        $result['redacted'] = $redacted;
        $result['count'] = substr_count($redacted, 'Fname')
            + substr_count($redacted, 'Lname')
            + substr_count($redacted, '[link]')
            + substr_count($redacted, '[email]')
            + substr_count($redacted, '[phone]')
            + substr_count($redacted, '[address]');
        return $result;
    }
}
