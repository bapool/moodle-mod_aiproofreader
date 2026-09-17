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
 * Confirms and performs returning a submission to draft status, so a
 * teacher can send a submitted (or already-graded) paper back to the
 * student for revision.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/aiproofreader/lib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('aiproofreader', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$aiproofreader = $DB->get_record('aiproofreader', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/aiproofreader:grade', $context);

$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$submission = $DB->get_record(
    'aiproofreader_submission',
    ['aiproofreaderid' => $aiproofreader->id, 'userid' => $userid]
);

$listurl = new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]);

if (!$submission || !in_array($submission->status, ['finalsubmitted', 'graded'])) {
    redirect($listurl);
}

$returnurl = new moodle_url('/mod/aiproofreader/returntodraft.php', ['id' => $cm->id, 'userid' => $userid]);

if ($confirm && confirm_sesskey()) {
    \mod_aiproofreader\submission_manager::return_to_draft($aiproofreader, $submission);

    redirect(
        $listurl,
        get_string('returnedtodraft', 'aiproofreader', fullname($student)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url($returnurl);
$PAGE->set_title(format_string($aiproofreader->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$message = get_string('confirmreturntodraft', 'aiproofreader', fullname($student));
if ($submission->status === 'graded') {
    $message .= ' ' . get_string('confirmreturntodraft_gradewarning', 'aiproofreader');
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    $message,
    new moodle_url($returnurl, ['confirm' => 1, 'sesskey' => sesskey()]),
    $listurl
);
echo $OUTPUT->footer();
