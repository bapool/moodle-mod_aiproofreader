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
 * Each text goes through \mod_aiproofreader\local\redactor: names of
 * everyone enrolled in the course and Google Docs/Drive links are replaced
 * in code first, then the AI redacts any other names and contact details,
 * then the code step runs once more. AI output whose length is far from the
 * original (for texts of 500+ characters) is rejected and nothing is saved,
 * so it is retried on the next run.
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

            // Known names in code, then the AI, then the length check and the
            // known names again - see \mod_aiproofreader\local\redactor.
            $result = \mod_aiproofreader\local\redactor::redact(
                (string) $submission->$textfield,
                (int) $aiproofreader->course,
                (int) $context->id,
                (int) $submission->userid
            );

            if (!$result['success']) {
                $failed++;
                mtrace('  submission ' . $submission->id . ': ' . $result['error'] . ', will retry next run.');
                continue;
            }

            $update = new \stdClass();
            $update->id = $submission->id;
            $update->$redactedfield = $result['redacted'];
            $update->$redactedatfield = time();
            $update->$countfield = $result['count'];
            $DB->update_record('aiproofreader_submission', $update);
            $processed++;
        }

        $rs->close();

        mtrace(
            'mod_aiproofreader: redacted ' . $which . ' text for ' . $processed
            . ' submission(s), ' . $failed . ' failure(s) left for next run.'
        );
    }
}
