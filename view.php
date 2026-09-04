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
 * View page for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/aiproofreader/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$retry = optional_param('retry', 0, PARAM_BOOL);
$groupid = optional_param('groupid', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('aiproofreader', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $aiproofreader = $DB->get_record('aiproofreader', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'aiproofreader');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/aiproofreader:view', $context);

$event = \mod_aiproofreader\event\course_module_viewed::create([
    'objectid' => $aiproofreader->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('aiproofreader', $aiproofreader);
$event->trigger();

$PAGE->set_url('/mod/aiproofreader/view.php', ['id' => $cm->id, 'groupid' => $groupid]);
$PAGE->set_title(format_string($aiproofreader->name));
$PAGE->requires->js_call_amd('mod_aiproofreader/tts', 'init');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if (isset($PAGE->activityheader)) {
    $PAGE->activityheader->set_description('', true);
}

$isgrader = has_capability('mod/aiproofreader:viewallsubmissions', $context);

// All form processing (and any redirects it triggers) happens BEFORE any
// output, per Moodle convention - redirect() after output has started falls
// back to a "click Continue" interstitial instead of a clean HTTP redirect.
$submission = null;
$draftform = null;
$finalform = null;

if (!$isgrader) {
    require_capability('mod/aiproofreader:submit', $context);

    $submission = \mod_aiproofreader\submission_manager::get_or_create($aiproofreader->id, $USER->id);

    if ($retry && $submission->status === 'feedbackpending' && confirm_sesskey()) {
        \mod_aiproofreader\submission_manager::generate_feedback($aiproofreader, $submission);
        redirect(new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]));
    }

    if ($submission->status === 'draft') {
        $draftform = new \mod_aiproofreader\form\draft_form(
            $PAGE->url,
            ['aiproofreader' => $aiproofreader, 'context' => $context]
        );

        if ($data = $draftform->get_data()) {
            $onlinetext = aiproofreader_editor_html_to_text($data->onlinetext_editor['text'] ?? '');
            \mod_aiproofreader\submission_manager::save_draft(
                $aiproofreader,
                $submission,
                $context,
                $data->submissiontype,
                $onlinetext,
                $data->gdrivelink ?? '',
                $data->submissionfile ?? 0
            );
            redirect(new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]));
        }
    } else if ($submission->status === 'feedbackready') {
        $finalform = new \mod_aiproofreader\form\final_form(
            $PAGE->url,
            ['aiproofreader' => $aiproofreader, 'context' => $context, 'submission' => $submission]
        );

        if ($data = $finalform->get_data()) {
            $surveydata = [
                'q1overallfeedback' => $data->q1overallfeedback ?? null,
                'q2specificfeedback' => $data->q2specificfeedback ?? null,
                'q3usedfeedback' => $data->q3usedfeedback ?? null,
                'q4categoryhelped' => $data->q4categoryhelped ?? null,
                'q5confidence' => $data->q5confidence ?? null,
                'freetext' => $data->freetext ?? '',
            ];

            $onlinetext = aiproofreader_editor_html_to_text($data->onlinetext_editor['text'] ?? '');
            \mod_aiproofreader\submission_manager::submit_final(
                $aiproofreader,
                $submission,
                $context,
                $data->submissiontype,
                $onlinetext,
                $data->gdrivelink ?? '',
                $data->submissionfile ?? 0,
                $surveydata
            );
            redirect(new moodle_url('/mod/aiproofreader/view.php', ['id' => $cm->id]));
        }
    }
}

// ---- All output starts here; nothing above this line echoes anything. ----

echo $OUTPUT->header();

// Additional files (e.g. a lab sheet), shown to everyone who can view the activity.
$fs = get_file_storage();
$attachments = $fs->get_area_files($context->id, 'mod_aiproofreader', 'additionalfiles', 0, 'filename', false);
if (!empty($attachments)) {
    echo html_writer::start_tag('div', ['class' => 'aiproofreader-additionalfiles']);
    echo html_writer::tag('h5', get_string('additionalfiles', 'aiproofreader'));
    echo html_writer::start_tag('ul');
    foreach ($attachments as $file) {
        $url = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_aiproofreader',
            'additionalfiles',
            0,
            '/',
            $file->get_filename()
        );
        echo html_writer::tag('li', html_writer::link($url, $file->get_filename()));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_tag('div');
}

if ($isgrader) {
    echo aiproofreader_render_instructions($aiproofreader, $cm, true);

    // Minimal read-only overview for now; the full grading page is next.
    echo $OUTPUT->heading(get_string('teacheroverviewheading', 'aiproofreader'), 3);

    $groups = groups_get_all_groups($course->id);

    if (!empty($groups)) {
        echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'aiproofreader-groupfilter form-inline mb-3']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);

        $groupoptions = [0 => get_string('allparticipants')];
        foreach ($groups as $group) {
            $groupoptions[$group->id] = format_string($group->name);
        }

        echo html_writer::tag(
            'label',
            get_string('group') . ' ',
            ['for' => 'aiproofreader-groupid', 'class' => 'mr-2']
        );
        echo html_writer::select(
            $groupoptions,
            'groupid',
            $groupid,
            null,
            ['id' => 'aiproofreader-groupid', 'onchange' => 'this.form.submit()']
        );
        echo html_writer::end_tag('form');
    }

    $students = get_enrolled_users($context, 'mod/aiproofreader:submit', 0, 'u.*', null, 0, 0, true);

    if (!empty($groupid)) {
        $groupmemberids = groups_get_members($groupid, 'u.id');
        $students = array_intersect_key($students, $groupmemberids);
    }

    $submissionrecords = $DB->get_records('aiproofreader_submission', ['aiproofreaderid' => $aiproofreader->id]);

    $byuserid = [];
    foreach ($submissionrecords as $s) {
        $byuserid[$s->userid] = $s;
    }

    $table = new html_table();
    $table->head = [get_string('fullname'), get_string('yourstatus', 'aiproofreader')];

    foreach ($students as $student) {
        $status = isset($byuserid[$student->id]) ? $byuserid[$student->id]->status : 'draft';
        $statustext = get_string('status' . $status, 'aiproofreader');

        if (in_array($status, ['finalsubmitted', 'graded'])) {
            $gradeurl = new moodle_url('/mod/aiproofreader/grade.php', ['id' => $cm->id, 'userid' => $student->id]);
            $statustext = html_writer::link($gradeurl, $statustext);
        }

        $table->data[] = [fullname($student), $statustext];
    }

    echo html_writer::table($table);
} else {
    echo aiproofreader_render_instructions($aiproofreader, $cm, $submission->status !== 'draft');

    if ($submission->status === 'feedbackready'
            && $submission->initialsubmissiontype === 'text'
            && !empty($submission->initialtext)) {
        echo aiproofreader_collapsible_section(
            get_string('yourdraftheading', 'aiproofreader'),
            format_text($submission->initialtext, FORMAT_PLAIN)
        );
    }

    if ($submission->status === 'draft') {
        echo $OUTPUT->heading(get_string('draftsubmissionheading', 'aiproofreader'), 3);
        $draftform->display();
    } else if ($submission->status === 'feedbackpending') {
        $PAGE->requires->js_call_amd('mod_aiproofreader/feedback', 'generateFeedback', [$submission->id]);

        echo html_writer::start_tag('div', ['class' => 'aiproofreader-spinner d-flex align-items-center']);
        echo html_writer::tag('div', '', ['class' => 'spinner-border mr-2', 'role' => 'status']);
        echo html_writer::tag('span', get_string('generatingfeedback', 'aiproofreader'));
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', ['id' => 'aiproofreader-feedback-status', 'class' => 'd-none mt-3']);
        echo $OUTPUT->notification(get_string('generatingfeedbackerror', 'aiproofreader'), 'notifyproblem');
        echo html_writer::tag('p', '', ['id' => 'aiproofreader-error-detail', 'class' => 'text-muted small']);
        $retryurl = new moodle_url(
            '/mod/aiproofreader/view.php',
            ['id' => $cm->id, 'retry' => 1, 'sesskey' => sesskey()]
        );
        echo html_writer::link($retryurl, get_string('retrybutton', 'aiproofreader'), ['class' => 'btn btn-secondary']);
        echo html_writer::end_tag('div');
    } else if ($submission->status === 'feedbackready') {
        $PAGE->requires->js_call_amd('mod_aiproofreader/feedback', 'fixCategoryQuestionLayout');

        echo aiproofreader_render_feedback_block($submission);
        $finalform->display();
    } else if ($submission->status === 'finalsubmitted') {
        // The AI comparison is generated synchronously in submit_final(), so
        // it's already available here - no async trigger needed.
        echo aiproofreader_render_feedback_block($submission);

        if ($submission->finalsubmissiontype === 'gdrive' && empty($submission->finaltext)) {
            echo $OUTPUT->notification(get_string('gdrivefetchfailed', 'aiproofreader'), 'notifyproblem');
        }

        echo $OUTPUT->notification(get_string('waitingforgrade', 'aiproofreader'), 'notifysuccess');
    } else if ($submission->status === 'graded') {
        echo aiproofreader_render_feedback_block($submission);

        $grade = $DB->get_record('aiproofreader_grade', ['submissionid' => $submission->id]);
        if ($grade) {
            echo $OUTPUT->box_start('generalbox aiproofreader-grade');
            echo $OUTPUT->heading(get_string('gradedheading', 'aiproofreader'), 3);
            echo html_writer::tag('p', get_string(
                'yourgrade',
                'aiproofreader',
                ['grade' => $grade->grade, 'max' => $aiproofreader->grade]
            ));
            if (!empty($grade->instructorcomments)) {
                echo html_writer::tag('h4', get_string('instructorcomments', 'aiproofreader'));
                echo format_text($grade->instructorcomments, $grade->instructorcommentsformat);
            }
            echo $OUTPUT->box_end();
        }
    }
}

echo $OUTPUT->footer();
