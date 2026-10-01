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
    ['check' => false, 'reset' => false, 'courseid' => 0, 'help' => false],
    ['c' => 'check', 'r' => 'reset', 'h' => 'help']
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
--courseid=ID       Only check/reset submissions in this course.
-h, --help          Print this help.

Examples:
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --check
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --reset
\$ sudo -u www-data php mod/aiproofreader/cli/reset_redaction.php --reset --courseid=42

After --reset, run the task now instead of waiting for tonight (repeat until it
reports 0 redacted, if there are more submissions than the batch size):
\$ sudo -u www-data php admin/cli/scheduled_task.php --execute='\\mod_aiproofreader\\task\\redact_pii'
";

if ($options['help'] || (!$options['check'] && !$options['reset'])) {
    echo $help;
    exit(0);
}

$courseid = (int) $options['courseid'];
$params = $courseid ? ['courseid' => $courseid] : [];

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
