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
 * "Grade now" page: lets a teacher enter a grade (for example a zero, or
 * partial credit for a draft) for a student who has not made a final
 * submission yet, at any stage. The student's draft and AI feedback are
 * shown when they exist. The assignment stays open - the student can still complete it, and it then
 * shows as "Submitted after grading" so the teacher can regrade it.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/aiproofreader/lib.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$cm = get_coursemodule_from_id('aiproofreader', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$aiproofreader = $DB->get_record('aiproofreader', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/aiproofreader:grade', $context);

$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$viewurl = new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]);

// Only activities graded with points have a grade to enter.
if ((int) $aiproofreader->grade <= 0) {
    redirect($viewurl, get_string('gradenownotavailable', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
}

if (!is_enrolled($context, $student, 'mod/aiproofreader:submit')) {
    redirect($viewurl, get_string('gradenotstudent', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
}

$submission = $DB->get_record(
    'aiproofreader_submission',
    ['aiproofreaderid' => $aiproofreader->id, 'userid' => $userid]
);

// Before the due date, Grade now is only for updating a grade that already exists.
if (!aiproofreader_grade_now_available($aiproofreader)) {
    $hasgrade = $submission && $DB->record_exists('aiproofreader_grade', ['submissionid' => $submission->id]);
    if (!$hasgrade) {
        redirect($viewurl, get_string('gradenotyetdue', 'aiproofreader'), null, \core\output\notification::NOTIFY_WARNING);
    }
}

// A student who has made a final submission is graded on the full grading page.
if ($submission && in_array($submission->status, ['finalsubmitted', 'graded'])) {
    redirect(new moodle_url('/mod/aiproofreader/grade.php', ['id' => $cm->id, 'userid' => $userid]));
}

$PAGE->set_url('/mod/aiproofreader/gradenow.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_title(format_string($aiproofreader->name));
$PAGE->requires->js_call_amd('mod_aiproofreader/tts', 'init');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mform = new \mod_aiproofreader\form\gradenow_form($PAGE->url, ['aiproofreader' => $aiproofreader]);

if ($mform->is_cancelled()) {
    redirect($viewurl);
}

if ($data = $mform->get_data()) {
    \mod_aiproofreader\submission_manager::save_grade_now(
        $aiproofreader,
        $userid,
        $USER->id,
        (int) $data->grade,
        $data->instructorcomments_editor['text'],
        $data->instructorcomments_editor['format']
    );

    redirect(
        $viewurl,
        get_string('gradenowsaved', 'aiproofreader', fullname($student)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Preload an existing grade and comments if this is an update.
if (!$mform->is_submitted() && $submission) {
    $existinggrade = $DB->get_record('aiproofreader_grade', ['submissionid' => $submission->id]);
    if ($existinggrade) {
        $mform->set_data([
            'grade' => $existinggrade->grade,
            'instructorcomments_editor' => [
                'text' => $existinggrade->instructorcomments,
                'format' => $existinggrade->instructorcommentsformat,
            ],
        ]);
    }
}

$stage = get_string('status' . ($submission ? $submission->status : 'draft'), 'aiproofreader');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gradenowheading', 'aiproofreader', fullname($student)));

echo $OUTPUT->notification(
    get_string('gradenownotice', 'aiproofreader', ['name' => fullname($student), 'stage' => $stage]),
    \core\output\notification::NOTIFY_INFO,
    false
);

// Show whatever the student has done so far, so the teacher can give
// partial credit for a draft rather than grading blind. A submission in
// "draft" status has no draft submitted yet; from "feedbackpending" on,
// the draft is stored.
if ($submission && in_array($submission->status, ['feedbackpending', 'feedbackready'])) {
    echo aiproofreader_collapsible_section(
        get_string('draftsubmissionheading', 'aiproofreader'),
        aiproofreader_render_submission_content($context, $submission, 'draft'),
        true
    );

    if ($submission->status === 'feedbackpending') {
        echo $OUTPUT->notification(
            get_string('gradenowfeedbackpending', 'aiproofreader'),
            \core\output\notification::NOTIFY_INFO,
            false
        );
    } else {
        echo aiproofreader_render_feedback_block($submission);
    }

    // A submission returned to draft still holds the final version the
    // student made before it was returned.
    if (!empty($submission->finaltimesubmitted)) {
        echo aiproofreader_collapsible_section(
            get_string('gradenowpreviousfinal', 'aiproofreader'),
            aiproofreader_render_submission_content($context, $submission, 'final'),
            false
        );
    }
}

$mform->display();

echo $OUTPUT->footer();
