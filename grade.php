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
 * Grading page for mod_aiproofreader: shows the draft, AI feedback, final
 * version, AI comparison, and the student's survey answers, then collects
 * the teacher's grade, comments, and required survey.
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

$submission = $DB->get_record(
    'aiproofreader_submission',
    ['aiproofreaderid' => $aiproofreader->id, 'userid' => $userid]
);

$viewurl = new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]);

if (!$submission || !in_array($submission->status, ['finalsubmitted', 'graded'])) {
    redirect(
        $viewurl,
        get_string('nothingtogradeyet', 'aiproofreader'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$PAGE->set_url('/mod/aiproofreader/grade.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_title(format_string($aiproofreader->name));
$PAGE->requires->js_call_amd('mod_aiproofreader/tts', 'init');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mform = new \mod_aiproofreader\form\grade_form(
    $PAGE->url,
    ['aiproofreader' => $aiproofreader, 'cmid' => $cm->id, 'userid' => $userid]
);

if ($mform->is_cancelled()) {
    redirect($viewurl);
}

if ($data = $mform->get_data()) {
    $surveydata = [
        'q1overallfeedback' => $data->q1overallfeedback ?? null,
        'q2specificfeedback' => $data->q2specificfeedback ?? null,
        'q3usedfeedback' => $data->q3usedfeedback ?? null,
        'q4feedbackfollowed' => $data->q4feedbackfollowed ?? null,
        'q5aiscaffold' => $data->q5aiscaffold ?? null,
        'q6aiaccuracy' => $data->q6aiaccuracy ?? null,
        'freetext' => $data->freetext ?? '',
    ];

    \mod_aiproofreader\submission_manager::save_grade(
        $aiproofreader,
        $submission,
        $USER->id,
        $data->grade ?? 0,
        $data->instructorcomments_editor['text'],
        $data->instructorcomments_editor['format'],
        $surveydata
    );

    redirect($viewurl, get_string('gradesaved', 'aiproofreader'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Preload an existing grade/comments/survey if this student is being re-graded.
if (!$mform->is_submitted()) {
    $existinggrade = $DB->get_record('aiproofreader_grade', ['submissionid' => $submission->id]);
    if ($existinggrade) {
        $preloaddata = [
            'grade' => $existinggrade->grade,
            'instructorcomments_editor' => [
                'text' => $existinggrade->instructorcomments,
                'format' => $existinggrade->instructorcommentsformat,
            ],
        ];

        $existingsurvey = $DB->get_record('aiproofreader_teachersurvey', ['submissionid' => $submission->id]);
        if ($existingsurvey) {
            $surveyfields = [
                'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
                'q4feedbackfollowed', 'q5aiscaffold', 'q6aiaccuracy',
            ];
            foreach ($surveyfields as $qkey) {
                if ($existingsurvey->$qkey !== null) {
                    $preloaddata[$qkey] = $existingsurvey->$qkey;
                }
            }
            $preloaddata['freetext'] = $existingsurvey->freetext;
        }

        $mform->set_data($preloaddata);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(fullname($student));

echo aiproofreader_collapsible_section(
    get_string('draftsubmissionheading', 'aiproofreader'),
    aiproofreader_render_submission_content($context, $submission, 'draft'),
    true
);

echo aiproofreader_render_feedback_block($submission);

echo aiproofreader_collapsible_section(
    get_string('finalsubmissionheading', 'aiproofreader'),
    aiproofreader_render_submission_content($context, $submission, 'final'),
    true
);

// Whether the final version differs from the draft. This is the only
// change check available for Google Drive and uploaded submissions.
$finalchanged = $submission->finalchanged ?? null;
if ($finalchanged === null) {
    // Submissions made before this check existed.
    $finalchanged = \mod_aiproofreader\submission_manager::detect_final_changed($submission);
}
if ($finalchanged === null) {
    $changedkey = 'finalchanged_unknown';
    $changedtype = 'notifywarning';
} else if ((int) $finalchanged === 1) {
    $changedkey = 'finalchanged_yes';
    $changedtype = 'notifysuccess';
} else {
    $changedkey = 'finalchanged_no';
    $changedtype = 'notifyproblem';
}
echo $OUTPUT->notification(
    html_writer::tag('strong', get_string('finalchangedheading', 'aiproofreader')) . ' '
        . get_string($changedkey, 'aiproofreader'),
    $changedtype,
    false
);

if (!empty($submission->finaltext) && !aiproofreader_meets_minlength($aiproofreader, $submission->finaltext)) {
    echo $OUTPUT->notification(
        get_string('finalbelowminimum', 'aiproofreader', aiproofreader_minlength_description($aiproofreader)),
        'notifywarning',
        false
    );
}

$studentsurvey = $DB->get_record('aiproofreader_studentsurvey', ['submissionid' => $submission->id]);
if ($studentsurvey) {
    echo aiproofreader_collapsible_section(
        get_string('studentsurveyheading', 'aiproofreader'),
        aiproofreader_render_student_survey($studentsurvey),
        false
    );
}

if (isset($submission->aifollowedscore) && $submission->aifollowedscore !== null) {
    echo html_writer::tag(
        'p',
        html_writer::tag('strong', get_string('aifollowedscoreheading', 'aiproofreader'))
        . ' ' . $submission->aifollowedscore . '/5',
        ['class' => 'aiproofreader-followedscore']
    );
} else if (
    ($submission->initialsubmissiontype === 'gdrive' && empty($submission->initialtext))
        || ($submission->finalsubmissiontype === 'gdrive' && empty($submission->finaltext))
) {
    echo $OUTPUT->notification(get_string('gdrivefetchfailed', 'aiproofreader'), 'notifyproblem');
}

$mform->display();

echo $OUTPUT->footer();
