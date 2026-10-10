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
 * "Finalize grade": for a student who submitted a draft but never made a
 * final submission, closes the assignment at the end of a grading period
 * by using the draft as the final version, then opens the normal grading
 * page (with the teacher survey). The teacher overview asks for
 * confirmation in a popup first; this page shows the same warning as a
 * confirmation page when JavaScript is unavailable.
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

$listurl = new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]);

if (!is_enrolled($context, $student, 'mod/aiproofreader:submit')) {
    redirect($listurl, get_string('gradenotstudent', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
}

// Same timing rule as "Grade now": not before the due date.
if (!aiproofreader_grade_now_available($aiproofreader)) {
    redirect($listurl, get_string('gradenotyetdue', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
}

$submission = $DB->get_record(
    'aiproofreader_submission',
    ['aiproofreaderid' => $aiproofreader->id, 'userid' => $userid]
);

// Only a draft that has its AI feedback and no final version yet can be finalized.
if (!$submission || $submission->status !== 'feedbackready') {
    redirect($listurl, get_string('finalizenotavailable', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
}

$finalizeurl = new moodle_url('/mod/aiproofreader/finalize.php', ['id' => $cm->id, 'userid' => $userid]);

if ($confirm && confirm_sesskey()) {
    \mod_aiproofreader\submission_manager::finalize_draft($aiproofreader, $submission, $context);

    redirect(
        new moodle_url('/mod/aiproofreader/grade.php', ['id' => $cm->id, 'userid' => $userid]),
        get_string('finalizedone', 'aiproofreader', fullname($student)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url($finalizeurl);
$PAGE->set_title(format_string($aiproofreader->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('finalizeconfirmtitle', 'aiproofreader'));
echo $OUTPUT->confirm(
    get_string('finalizeconfirm', 'aiproofreader', fullname($student)),
    new single_button(
        new moodle_url($finalizeurl, ['confirm' => 1, 'sesskey' => sesskey()]),
        get_string('finalizeconfirmbutton', 'aiproofreader'),
        'post',
        single_button::BUTTON_PRIMARY
    ),
    $listurl
);
echo $OUTPUT->footer();
