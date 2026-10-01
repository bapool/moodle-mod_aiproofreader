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

/**
 * Nightly task that creates AI-redacted, de-identified copies of student
 * submission text, for safe use when releasing writing samples publicly.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\task;

/**
 * Scheduled task: redact PII from any draft/final submission text that
 * hasn't been redacted yet.
 *
 * Names of everyone enrolled in the course are replaced in code first (see
 * \mod_aiproofreader\local\pii_names), then the AI redacts any other names
 * and contact details, then the known names are applied once more. AI output
 * whose length is far from the original is rejected and retried.
 *
 * To redo every redaction (for example after improving these steps), clear
 * the existing results with cli/reset_redaction.php.
 *
 * The original text is never modified - the redacted copy is written to a
 * separate field, so the originals stay available for normal grading and a
 * mistake here is always recoverable. Progress is tracked per-row (via the
 * "redacted at" timestamp fields) rather than by a last-run cutoff, so a
 * failed or interrupted run is simply retried the next night without
 * needing any extra bookkeeping.
 */
class redact_pii extends \core\task\scheduled_task {
    /** @var float Smallest acceptable length of the AI output, relative to the text sent. */
    const MIN_LENGTH_RATIO = 0.8;

    /** @var float Largest acceptable length of the AI output, relative to the text sent. */
    const MAX_LENGTH_RATIO = 1.25;

    /**
     * Returns the name of this task, shown in the scheduled tasks admin UI.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_redactpii', 'aiproofreader');
    }

    /**
     * Runs the task.
     */
    public function execute() {
        if (empty(get_config('aiproofreader', 'piiredactionenabled'))) {
            mtrace('mod_aiproofreader: PII redaction is disabled in site settings, skipping.');
            return;
        }

        $batchsize = (int) get_config('aiproofreader', 'piiredactionbatchsize');
        if ($batchsize <= 0) {
            $batchsize = 200;
        }

        $this->redact_field($batchsize, 'initial');
        $this->redact_field($batchsize, 'final');
    }

    /**
     * Processes one batch of not-yet-redacted rows for either the draft
     * ('initial') or final ('final') submission text.
     *
     * @param int $batchsize Maximum rows to process in this call
     * @param string $which 'initial' or 'final'
     */
    protected function redact_field($batchsize, $which) {
        global $DB;

        $textfield = $which . 'text';
        $redactedfield = $which . 'textredacted';
        $redactedatfield = $which . 'textredactedat';
        $countfield = $which . 'textpiicount';

        $sql = "SELECT *
                  FROM {aiproofreader_submission}
                 WHERE $textfield IS NOT NULL
                   AND " . $DB->sql_compare_text($textfield) . " != ?
                   AND $redactedatfield IS NULL
              ORDER BY id ASC";

        $rs = $DB->get_recordset_sql($sql, [''], 0, $batchsize);

        $processed = 0;
        $failed = 0;

        foreach ($rs as $submission) {
            $aiproofreader = $DB->get_record('aiproofreader', ['id' => $submission->aiproofreaderid]);
            if (!$aiproofreader) {
                continue;
            }

            $cm = get_coursemodule_from_instance(
                'aiproofreader',
                $aiproofreader->id,
                $aiproofreader->course,
                false,
                IGNORE_MISSING
            );
            if (!$cm) {
                continue;
            }
            $context = \context_module::instance($cm->id);

            // Step 1: replace the names Moodle already knows (everyone
            // enrolled in the course - the student, classmates, teachers)
            // in code, exactly. The AI can only guess at names, and those
            // names are then never sent to the AI at all.
            $knownnames = \mod_aiproofreader\local\pii_names::for_course($aiproofreader->course);
            $original = (string) $submission->$textfield;
            $prepared = \mod_aiproofreader\local\pii_names::apply($original, $knownnames);

            // Step 2: the AI redacts anything else (family, friends, people
            // outside the course, contact details).
            $prompt = get_string('piiredactionprompt', 'aiproofreader', $prepared);

            try {
                $action = new \core_ai\aiactions\generate_text(
                    contextid: $context->id,
                    userid: $submission->userid,
                    prompttext: $prompt
                );
                $manager = \core\di::get(\core_ai\manager::class);
                $response = $manager->process_action($action);

                if (!$response->get_success()) {
                    $failed++;
                    mtrace('  submission ' . $submission->id . ': AI request unsuccessful - ' . $response->get_errormessage());
                    continue;
                }

                $redacted = trim($response->get_response_data()['generatedcontent'] ?? '');
                if ($redacted === '') {
                    $failed++;
                    mtrace('  submission ' . $submission->id . ': AI returned empty content, will retry next run.');
                    continue;
                }

                // Step 3: the AI must return the same text with only names
                // and contact details swapped. If it is far shorter or longer
                // it summarized, rewrote, refused or added commentary -
                // reject it and try again next run.
                $ratio = \core_text::strlen($redacted) / max(1, \core_text::strlen($prepared));
                if ($ratio < self::MIN_LENGTH_RATIO || $ratio > self::MAX_LENGTH_RATIO) {
                    $failed++;
                    mtrace('  submission ' . $submission->id . ': AI output length looks wrong (' . round($ratio, 2)
                        . 'x the original), will retry next run.');
                    continue;
                }

                // Step 4: re-apply the known names, in case the AI put any back.
                $redacted = \mod_aiproofreader\local\pii_names::apply($redacted, $knownnames);

                $count = substr_count($redacted, 'Fname')
                    + substr_count($redacted, 'Lname')
                    + substr_count($redacted, '[email]')
                    + substr_count($redacted, '[phone]')
                    + substr_count($redacted, '[address]');

                $update = new \stdClass();
                $update->id = $submission->id;
                $update->$redactedfield = $redacted;
                $update->$redactedatfield = time();
                $update->$countfield = $count;
                $DB->update_record('aiproofreader_submission', $update);
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
                mtrace('  submission ' . $submission->id . ': exception - ' . $e->getMessage());
            }
        }

        $rs->close();

        mtrace(
            'mod_aiproofreader: redacted ' . $which . ' text for ' . $processed
            . ' submission(s), ' . $failed . ' failure(s) left for next run.'
        );
    }
}
