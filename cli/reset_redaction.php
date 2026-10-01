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
 * Checks or resets the nightly PII redaction results.
 *
 * --test redacts one submission and prints every step without saving.
 * --check lists every redacted draft/final that still contains the name of
 * anyone enrolled in its course, so you can see whether redaction is
 * working. --reset clears the redacted copies (never the student's original
 * text), so the nightly task redoes them - for example after the redaction
 * steps are improved. Both can be limited to one course.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['check' => false, 'reset' => false, 'test' => 0, 'courseid' => 0, 'help' => false],
    ['c' => 'check', 'r' => 'reset', 't' => 'test', 'h' => 'help']
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

$help = "Check or reset AI Proofreader PII redaction results.

Options:
-c, --check         List redacted drafts/finals that still contain the name of
                    anyone enrolled in the course (student, classmate or teacher).
-r, --reset         Clear the redacted copies so the nightly task redoes them.
                    The students' original text is never changed.
-t, --test=ID       Redact one submission (by submission id) and print the text
                    sent to the AI, the AI's answer and the result, WITHOUT
                    saving anything.
--courseid=ID       Only check/reset submissions in this course.
-h, --help          Print this help.

Examples:
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --check
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --reset
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --reset --courseid=42
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --test=8

After --reset, run the task now instead of waiting for tonight (repeat until it
reports 0 redacted, if there are more submissions than the batch size):
\$ sudo -u www-data php admin/cli/scheduled_task.php --execute='\\mod_aiproofreader\\task\\redact_pii'
";

if ($options['help'] || (!$options['check'] && !$options['reset'] && !$options['test'])) {
    echo $help;
    exit(0);
}

$courseid = (int) $options['courseid'];
$params = $courseid ? ['courseid' => $courseid] : [];

if ($options['test']) {
    $submission = $DB->get_record('aiproofreader_submission', ['id' => (int) $options['test']]);
    if (!$submission) {
        cli_error('No AI Proofreader submission with id ' . (int) $options['test']);
    }
    $aiproofreader = $DB->get_record('aiproofreader', ['id' => $submission->aiproofreaderid], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('aiproofreader', $aiproofreader->id, $aiproofreader->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    foreach (['initialtext' => 'DRAFT', 'finaltext' => 'FINAL'] as $field => $label) {
        if ($submission->$field === null || trim($submission->$field) === '') {
            continue;
        }
        cli_writeln('');
        cli_writeln("==================== {$label} (submission {$submission->id}) ====================");
        $result = \mod_aiproofreader\local\redactor::redact(
            (string) $submission->$field,
            (int) $aiproofreader->course,
            (int) $context->id,
            (int) $submission->userid
        );
        cli_writeln('--- After the code step (known names and Google links replaced):');
        cli_writeln($result['prepared']);
        if ($result['skippedai']) {
            cli_writeln('--- Nothing but placeholders left, so the AI was not called.');
        } else {
            cli_writeln('--- AI answer:');
            cli_writeln($result['raw'] !== '' ? $result['raw'] : '(none)');
            cli_writeln('--- Length of answer: ' . round($result['ratio'], 2) . 'x the text sent (checked only for texts of '
                . \mod_aiproofreader\local\redactor::MIN_CHECK_LENGTH . '+ characters; accepted '
                . \mod_aiproofreader\local\redactor::MIN_LENGTH_RATIO . ' to '
                . \mod_aiproofreader\local\redactor::MAX_LENGTH_RATIO . ')');
        }
        if ($result['success']) {
            cli_writeln("--- RESULT: OK, {$result['count']} placeholder(s). This is what the task would save:");
            cli_writeln($result['redacted']);
        } else {
            cli_writeln('--- RESULT: REJECTED - ' . $result['error'] . '. The task would save nothing and retry.');
        }
    }
    cli_writeln('');
    cli_writeln('Nothing was saved.');
}

if ($options['check']) {
    $sql = "SELECT s.id, s.userid, s.initialtextredacted, s.finaltextredacted, a.course, a.name
              FROM {aiproofreader_submission} s
              JOIN {aiproofreader} a ON a.id = s.aiproofreaderid" . ($courseid ? ' WHERE a.course = :courseid' : '') . "
          ORDER BY a.course, a.id, s.id";
    $rs = $DB->get_recordset_sql($sql, $params);

    $checked = 0;
    $problems = 0;
    foreach ($rs as $row) {
        $names = \mod_aiproofreader\local\pii_names::for_course($row->course);
        foreach (['initialtextredacted' => 'draft', 'finaltextredacted' => 'final'] as $field => $label) {
            if ($row->$field === null || $row->$field === '') {
                continue;
            }
            $checked++;
            $found = \mod_aiproofreader\local\pii_names::find($row->$field, $names);
            if ($found) {
                $problems++;
                cli_writeln("course {$row->course}, \"{$row->name}\", submission {$row->id}, {$label}: "
                    . implode(', ', $found));
            }
        }
    }
    $rs->close();

    cli_writeln("Checked {$checked} redacted text(s); {$problems} still contain a known name.");
}

if ($options['reset']) {
    $sql = "UPDATE {aiproofreader_submission}
               SET initialtextredacted = NULL, initialtextredactedat = NULL, initialtextpiicount = NULL,
                   finaltextredacted = NULL, finaltextredactedat = NULL, finaltextpiicount = NULL";
    if ($courseid) {
        $sql .= " WHERE aiproofreaderid IN (SELECT id FROM {aiproofreader} WHERE course = :courseid)";
    }
    $DB->execute($sql, $params);

    cli_writeln('Redacted copies cleared' . ($courseid ? " for course {$courseid}" : '')
        . '. The nightly task will redo them, or run it now with:');
    cli_writeln("  php admin/cli/scheduled_task.php --execute='\\mod_aiproofreader\\task\\redact_pii'");
}
