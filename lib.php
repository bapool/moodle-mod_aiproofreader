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
 * Library of interface functions and constants for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Returns the Grade + Lexile option list for the mod_form gradelevel dropdown.
 * Lexile values are the published typical-reader norms for each grade.
 *
 * @return array
 */
function aiproofreader_gradelexile_options() {
    $options = [];
    foreach (aiproofreader_gradelexile_map() as $grade => $lexile) {
        $options[(string)$grade] = get_string(
            'gradelexile',
            'aiproofreader',
            ['grade' => $grade, 'lexile' => $lexile]
        );
    }
    return $options;
}

/**
 * Normalizes plain text for a loose equality comparison - collapses all
 * whitespace runs to a single space and lowercases, so trivial changes
 * (extra spaces, capitalization) don't count as "editing" the draft.
 *
 * @param string $text Plain text (not HTML).
 * @return string
 */
function aiproofreader_normalize_text_for_comparison($text) {
    $text = trim((string) $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return core_text::strtolower($text);
}

/**
 * The grade-to-lexile lookup table used both for the settings dropdown and
 * for building AI prompts later.
 *
 * @return array grade (int) => typical lexile measure (int)
 */
function aiproofreader_gradelexile_map() {
    return [
        3  => 520,
        4  => 740,
        5  => 830,
        6  => 925,
        7  => 970,
        8  => 1010,
        9  => 1050,
        10 => 1080,
        11 => 1185,
        12 => 1185,
    ];
}

/**
 * Looks up the typical lexile measure for a given grade level.
 *
 * @param int $grade
 * @return int|null
 */
function aiproofreader_get_lexile_for_grade($grade) {
    $map = aiproofreader_gradelexile_map();
    return isset($map[$grade]) ? $map[$grade] : null;
}

/**
 * Ordered list of student survey question keys.
 *
 * @return string[]
 */
function aiproofreader_student_survey_question_keys() {
    return ['q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback', 'q4categoryhelped', 'q5confidence', 'freetext'];
}

/**
 * Ordered list of teacher survey question keys.
 *
 * @return string[]
 */
function aiproofreader_teacher_survey_question_keys() {
    return [
        'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
        'q4feedbackfollowed', 'q5aiscaffold', 'q6aiaccuracy', 'freetext',
    ];
}

/**
 * Whether the survey system is turned on at all, site-wide. Off by default:
 * without local_aiproofreaderreport installed there is no way to see the
 * collected survey data, so nothing is collected or shown until it's on.
 *
 * @return bool
 */
function aiproofreader_survey_enabled() {
    return (bool) get_config('aiproofreader', 'surveyenabled');
}

/**
 * Whether one survey question is enabled for collection/display. Defaults
 * to enabled (matches this plugin's original behaviour) unless a site
 * admin has explicitly turned it off via local_aiproofreaderreport.
 *
 * @param string $surveytype student|teacher
 * @param string $qkey one of aiproofreader_student_survey_question_keys() / _teacher_
 * @return bool
 */
function aiproofreader_question_enabled($surveytype, $qkey) {
    $value = get_config('aiproofreader', $surveytype . 'survey_' . $qkey . '_enabled');
    return $value === false ? true : (bool) $value;
}

/**
 * The question wording to display: the site's customized text if one has
 * been set via local_aiproofreaderreport, otherwise this plugin's original
 * default wording.
 *
 * @param string $surveytype student|teacher
 * @param string $qkey one of aiproofreader_student_survey_question_keys() / _teacher_
 * @return string
 */
function aiproofreader_question_text($surveytype, $qkey) {
    $custom = get_config('aiproofreader', $surveytype . 'survey_' . $qkey . '_text');
    if ($custom !== false && trim((string) $custom) !== '') {
        return $custom;
    }

    $defaultstringkey = $surveytype === 'teacher' ? 'grader' . $qkey : $qkey;
    return get_string($defaultstringkey, 'aiproofreader');
}

/**
 * Whether Google Doc submission text, once fetched for AI processing,
 * should be retained in the database afterward - or purged back to just
 * the stored link once feedback/comparison no longer need it. Off by
 * default: a stored link with no retained text is not exportable research
 * data, so this must be explicitly turned on to collect it.
 *
 * @return bool
 */
function aiproofreader_collect_gdrive_text() {
    return (bool) get_config('aiproofreader', 'collectgdrivetext');
}

/**
 * The site's current label for whichever AI model/provider is configured
 * (e.g. "GPT-4o", "Claude Sonnet 4.5 via Anthropic"). Moodle's AI subsystem
 * doesn't reliably report which model actually answered a request - that's
 * up to each provider plugin, and it's inconsistent - so this manually
 * maintained label (set via local_aiproofreaderreport) is the reliable
 * record of what was in use, stamped onto each AI call. Update it whenever
 * the site's AI provider/model configuration changes.
 *
 * @return string Empty string if never set.
 */
function aiproofreader_get_current_ai_model_label() {
    $label = get_config('aiproofreader', 'currentaimodellabel');
    return $label === false ? '' : trim((string) $label);
}

/**
 * Returns a stable, one-way anonymous ID for a student, derived from their
 * Moodle idnumber (SSID) and a site-secret key that is never displayed or
 * exported. The same idnumber always produces the same anonid, forever,
 * with no mapping table stored anywhere - callers (e.g. anonymized export
 * code in local_aiproofreaderreport) simply call this each time they need
 * it. If the idnumber is empty, returns an empty string so callers can
 * decide how to handle students with no SSID on file.
 *
 * @param string $idnumber
 * @return string
 */
function aiproofreader_get_anon_id($idnumber) {
    $idnumber = trim((string) $idnumber);
    if ($idnumber === '') {
        return '';
    }

    $secret = aiproofreader_get_anon_id_secret();
    $hash = hash_hmac('sha256', $idnumber, $secret);

    // Compact, readable alphanumeric form - prefixed so it can never be
    // mistaken for a real student identifier.
    return 'ANON-' . strtoupper(substr($hash, 0, 12));
}

/**
 * Returns the site's secret key used to compute anonymous IDs, generating
 * and storing one automatically the first time it's needed. This value is
 * never shown in any settings screen, report, or export. Rotating it (by
 * deleting the config value) changes every anonid produced afterward.
 *
 * @return string
 */
function aiproofreader_get_anon_id_secret() {
    $secret = get_config('aiproofreader', 'anonidsecret');
    if ($secret === false || $secret === '') {
        $secret = bin2hex(random_bytes(32));
        set_config('anonidsecret', $secret, 'aiproofreader');
    }
    return $secret;
}

/**
 * Builds the value stored in feedbackaimodel/comparisonaimodel: the site's
 * configured label, plus whatever the AI response itself reported (if
 * anything - most providers don't), so nothing is lost either way.
 *
 * @param \core_ai\aiactions\responses\response_base $response
 * @return string
 */
function aiproofreader_build_ai_model_label($response) {
    $label = aiproofreader_get_current_ai_model_label();

    $reported = null;
    try {
        $data = $response->get_response_data();
        $reported = $data['model'] ?? null;
    } catch (\Throwable $e) {
        // Some response types may not support this - never let model
        // labeling break the actual AI feedback/comparison it's labeling.
        $reported = null;
    }

    if (!empty($reported) && $reported !== $label) {
        return $label !== '' ? ($label . ' (reported: ' . $reported . ')') : $reported;
    }

    return $label;
}

/**
 * Converts the HTML produced by a Moodle text editor (Atto/TinyMCE) into
 * plain text, preserving paragraph/line breaks as newlines. Used so the
 * student-facing rich text editor can be offered for a nicer typing/paste
 * experience while everything downstream (AI prompts, sentence counting,
 * plain-text storage) keeps working exactly as it did with a plain textarea.
 *
 * @param string $html
 * @return string
 */
function aiproofreader_editor_html_to_text($html) {
    $html = (string) $html;
    $html = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", $html);
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

/**
 * A simple heuristic sentence count: the number of terminal punctuation
 * clusters (., !, ?) in the text. Not linguistically perfect, but good
 * enough to gate an obviously too-short draft.
 *
 * @param string $text Plain text (not HTML).
 * @return int
 */
function aiproofreader_count_sentences($text) {
    $text = trim((string) $text);
    if ($text === '') {
        return 0;
    }
    preg_match_all('/[.!?]+/', $text, $matches);
    return count($matches[0]);
}

/**
 * File manager options for the teacher's "Additional files" attachments
 * (e.g. a lab sheet). Stored at itemid 0 since there is one set per instance.
 *
 * @return array
 */
function aiproofreader_get_additionalfiles_options() {
    global $COURSE;
    return [
        'subdirs' => 0,
        'maxbytes' => $COURSE->maxbytes,
        'maxfiles' => 20,
        'accepted_types' => '*',
        'return_types' => FILE_INTERNAL,
    ];
}

/**
 * Builds the submission-type option list (text/file/gdrive) based on which
 * types are enabled on the instance. Shared by draft_form and final_form.
 *
 * @param stdClass $aiproofreader
 * @return array value => label, in a fixed order
 */
function aiproofreader_enabled_type_options($aiproofreader) {
    $options = [];
    if (!empty($aiproofreader->submtext)) {
        $options['text'] = get_string('submtext', 'aiproofreader');
    }
    if (!empty($aiproofreader->submfile)) {
        $options['file'] = get_string('submfile', 'aiproofreader');
    }
    if (!empty($aiproofreader->submgdrive)) {
        $options['gdrive'] = get_string('submgdrive', 'aiproofreader');
    }
    return $options;
}

/**
 * Wraps content in a collapsible <details> element, styled consistently.
 *
 * @param string $title Shown as the clickable summary
 * @param string $content HTML content
 * @param bool $open Whether it starts expanded
 * @return string HTML
 */
function aiproofreader_collapsible_section($title, $content, $open = false) {
    $attrs = ['class' => 'aiproofreader-collapsible generalbox'];
    if ($open) {
        $attrs['open'] = 'open';
    }

    $out = html_writer::start_tag('details', $attrs);
    $out .= html_writer::tag('summary', $title);
    $out .= html_writer::div($content, 'aiproofreader-collapsible-content');
    $out .= html_writer::end_tag('details');

    return $out;
}

/**
 * Renders the activity instructions, either as a plain always-visible box
 * or as a collapsible <details> element.
 *
 * @param stdClass $aiproofreader
 * @param stdClass $cm
 * @param bool $collapsible False forces the instructions open with no way to collapse them
 * @return string HTML, or empty string if there is no intro
 */
function aiproofreader_render_instructions($aiproofreader, $cm, $collapsible = true) {
    if (empty($aiproofreader->intro)) {
        return '';
    }

    $ttstext = aiproofreader_editor_html_to_text($aiproofreader->intro);
    $content = aiproofreader_render_tts_button($ttstext) . format_module_intro('aiproofreader', $aiproofreader, $cm->id);

    if (!$collapsible) {
        return html_writer::div($content, 'aiproofreader-instructions generalbox mod_introbox');
    }

    return aiproofreader_collapsible_section(get_string('activityinstructions', 'aiproofreader'), $content, false);
}

/**
 * Builds a download link for a stored draft/final submission file, if one exists.
 *
 * @param context_module $context
 * @param int $itemid The aiproofreader_submission id
 * @param string $filearea draftsubmission|finalsubmission
 * @return string HTML link, or empty string if no file is stored
 */
function aiproofreader_render_submission_file_link($context, $itemid, $filearea) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_aiproofreader', $filearea, $itemid, 'id', false);
    $file = reset($files);

    if (!$file) {
        return '';
    }

    $url = moodle_url::make_pluginfile_url($context->id, 'mod_aiproofreader', $filearea, $itemid, '/', $file->get_filename());
    return html_writer::link($url, $file->get_filename());
}

/**
 * Renders a read-only summary of the student's survey answers, for the grader.
 * Returns just the content (no heading/wrapper) so callers can wrap it in
 * their own collapsible section.
 *
 * @param stdClass $survey
 * @return string HTML
 */
function aiproofreader_render_student_survey($survey) {
    $out = '';

    foreach (aiproofreader_student_survey_question_keys() as $qkey) {
        if (!aiproofreader_question_enabled('student', $qkey)) {
            continue;
        }

        if ($qkey === 'freetext') {
            if (!empty($survey->freetext)) {
                $out .= html_writer::tag('p', html_writer::tag('strong', get_string('freetextlabel', 'aiproofreader'))
                    . ' ' . s($survey->freetext));
            }
        } else if ($qkey === 'q4categoryhelped') {
            if (!empty($survey->q4categoryhelped)) {
                $out .= html_writer::tag('p', aiproofreader_question_text('student', $qkey) . ' '
                    . get_string('q4categoryhelped_' . $survey->q4categoryhelped, 'aiproofreader'));
            }
        } else if (isset($survey->$qkey) && $survey->$qkey !== null) {
            $out .= html_writer::tag('p', aiproofreader_question_text('student', $qkey) . ' ' . $survey->$qkey . '/5');
        }
    }

    return $out;
}

/**
 * Renders the draft or final submission content (text, file link, or Google Drive link).
 *
 * @param context_module $context
 * @param stdClass $submission
 * @param string $stage draft|final
 * @return string HTML
 */
function aiproofreader_render_submission_content($context, $submission, $stage) {
    $type = $stage === 'draft' ? $submission->initialsubmissiontype : $submission->finalsubmissiontype;
    $text = $stage === 'draft' ? $submission->initialtext : $submission->finaltext;
    $gdrivelink = $stage === 'draft' ? $submission->initialgdrivelink : $submission->finalgdrivelink;
    $filearea = $stage === 'draft' ? 'draftsubmission' : 'finalsubmission';

    if ($type === 'gdrive' && !empty($gdrivelink)) {
        return html_writer::link($gdrivelink, $gdrivelink, ['target' => '_blank', 'rel' => 'noopener']);
    } else if ($type === 'file') {
        return aiproofreader_render_submission_file_link($context, $submission->id, $filearea);
    } else {
        return html_writer::div(format_text($text, FORMAT_PLAIN), 'generalbox');
    }
}

/**
 * Renders the AI feedback (and AI comparison, once available) for a submission,
 * each piece as its own collapsible section. Shared between the student view
 * and the teacher grading page.
 *
 * @param stdClass $submission
 * @return string HTML
 */
/**
 * Renders a "Read aloud" button using the browser's built-in text-to-speech
 * (wired up client-side by amd/src/tts.js) - no server-side audio generation
 * involved. Returns an empty string if there's no text to read.
 *
 * @param string $text Plain text to be read aloud.
 * @return string HTML
 */
function aiproofreader_render_tts_button($text) {
    if (trim((string) $text) === '') {
        return '';
    }

    return html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'icon fa fa-volume-up fa-fw aiproofreader-tts-icon',
            'aria-hidden' => 'true',
        ]) . html_writer::tag('span', get_string('readaloud', 'aiproofreader'), ['class' => 'aiproofreader-tts-label']),
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary aiproofreader-tts-button mb-2',
            'data-tts-text' => $text,
            'data-tts-label-play' => get_string('readaloud', 'aiproofreader'),
            'data-tts-label-stop' => get_string('stopreading', 'aiproofreader'),
        ]
    );
}

/**
 * Renders the grammar, assignment, and AI-comparison feedback sections for
 * a submission's HTML view, as collapsible sections with read-aloud buttons.
 *
 * @param \stdClass $submission
 * @return string HTML
 */
function aiproofreader_render_feedback_block($submission) {
    $out = html_writer::tag('h3', get_string('feedbackheading', 'aiproofreader'));

    if (!empty($submission->feedbackgrammar)) {
        $out .= aiproofreader_collapsible_section(
            get_string('feedbackgrammarheading', 'aiproofreader'),
            aiproofreader_render_tts_button($submission->feedbackgrammar)
                . format_text($submission->feedbackgrammar, FORMAT_PLAIN),
            true
        );
    }

    if (!empty($submission->feedbackassignment)) {
        $out .= aiproofreader_collapsible_section(
            get_string('feedbackassignmentheading', 'aiproofreader'),
            aiproofreader_render_tts_button($submission->feedbackassignment)
                . format_text($submission->feedbackassignment, FORMAT_PLAIN),
            true
        );
    }

    if (!empty($submission->aicomparison)) {
        $out .= aiproofreader_collapsible_section(
            get_string('aicomparisonheading', 'aiproofreader'),
            aiproofreader_render_tts_button($submission->aicomparison)
                . format_text($submission->aicomparison, FORMAT_PLAIN),
            true
        );
    }

    return $out;
}

/**
 * Declares which Moodle features this module supports.
 *
 * @param string $feature FEATURE_xx constant
 * @return mixed
 */
function aiproofreader_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Populates extra course-modinfo data, including which custom completion
 * rules are actually turned on for this instance - required so Moodle's
 * completion engine knows to evaluate 'completionsubmit' rather than
 * silently ignoring it and marking the activity complete for everyone.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|null
 */


/**
 * Lists visible Assignment activities in a course, for the "import from an
 * existing Assignment" dropdown on the AI Proofreader add form.
 *
 * @param int $courseid
 * @return array cmid => activity name
 */
/**
 * Builds the "Assignment to import from" dropdown options: every visible
 * Assignment activity in the course, optionally narrowed to one section.
 *
 * @param int $courseid
 * @param int|null $sectionnum Course section number to restrict to, or null for the whole course
 * @return array cmid => name
 */
function aiproofreader_get_course_assign_options($courseid, $sectionnum = null) {
    $modinfo = get_fast_modinfo($courseid);
    $options = [];

    if (empty($modinfo->instances['assign'])) {
        return $options;
    }

    foreach ($modinfo->instances['assign'] as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        if ($sectionnum !== null && (int) $cm->sectionnum !== (int) $sectionnum) {
            continue;
        }
        $options[$cm->id] = format_string($cm->name);
    }

    return $options;
}

/**
 * Supplies course-page display info for an activity instance, including
 * custom completion rule data for the "completionsubmit" rule.
 *
 * @param \stdClass $coursemodule
 * @return \cached_cm_info|null
 */
function aiproofreader_get_coursemodule_info($coursemodule) {
    global $DB;

    $aiproofreader = $DB->get_record(
        'aiproofreader',
        ['id' => $coursemodule->instance],
        'id, name, completionsubmit'
    );

    if (!$aiproofreader) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $aiproofreader->name;

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionsubmit'] = $aiproofreader->completionsubmit;
    }

    return $info;
}

/**
 * Saves a new instance of mod_aiproofreader.
 *
 * @param stdClass $aiproofreader
 * @param mod_aiproofreader_mod_form|null $mform
 * @return int The new instance id
 */
function aiproofreader_add_instance($aiproofreader, $mform = null) {
    global $CFG, $DB;

    $aiproofreader->timecreated  = time();
    $aiproofreader->timemodified = time();

    if (isset($aiproofreader->aiinstructions_editor)) {
        $aiproofreader->aiinstructions       = $aiproofreader->aiinstructions_editor['text'];
        $aiproofreader->aiinstructionsformat = $aiproofreader->aiinstructions_editor['format'];
        unset($aiproofreader->aiinstructions_editor);
    }

    $aiproofreader->id = $DB->insert_record('aiproofreader', $aiproofreader);

    if ($mform) {
        $context = context_module::instance($aiproofreader->coursemodule);
        file_postupdate_standard_filemanager(
            $aiproofreader,
            'additionalfiles',
            aiproofreader_get_additionalfiles_options(),
            $context,
            'mod_aiproofreader',
            'additionalfiles',
            0
        );
    }

    aiproofreader_grade_item_update($aiproofreader);

    return $aiproofreader->id;
}

/**
 * Hook Moodle calls after a course module has been fully created or
 * updated (course_modules.instance is guaranteed to be set correctly by
 * this point, unlike inside add_instance()/update_instance() themselves).
 *
 * Used here to finish an Assignment Import: reposition the new activity
 * right after the source Assignment, copy its restrict-access conditions,
 * and hide the now-superseded Assignment (left in place, not deleted, for
 * the teacher's reference).
 *
 * This runs for every course module add/edit sitewide, not just ours, so
 * it must check the module type itself before doing anything.
 *
 * @param \stdClass $moduleinfo
 * @param \stdClass $course
 * @return \stdClass The (possibly unmodified) moduleinfo, as this hook's contract requires
 */
function aiproofreader_coursemodule_edit_post_actions($moduleinfo, $course) {
    global $CFG, $DB;

    if (($moduleinfo->modulename ?? '') !== 'aiproofreader' || empty($moduleinfo->sourceassigncmid)) {
        return $moduleinfo;
    }

    require_once($CFG->dirroot . '/course/lib.php');

    $sourcecmid = (int) $moduleinfo->sourceassigncmid;
    $sourcecm = $DB->get_record('course_modules', ['id' => $sourcecmid]);

    if ($sourcecm) {
        // Copy the raw restriction rule directly - this avoids needing
        // to replicate Moodle's JS-driven Restrict Access tree UI.
        $DB->set_field(
            'course_modules',
            'availability',
            $sourcecm->availability,
            ['id' => $moduleinfo->coursemodule]
        );

        $destsection = $DB->get_record('course_sections', ['id' => $sourcecm->section], '*', MUST_EXIST);

        // Find whichever course module currently follows the source
        // Assignment in its section, so the new activity can be
        // inserted immediately after it (or appended at the end if
        // the Assignment was already last). Skip the new activity's
        // own id: when adding within the same section, Moodle may have
        // already placed it right after the source Assignment before
        // this hook runs, which would otherwise make this look for
        // "the module before itself" and silently fail to reposition.
        $modinfo = get_fast_modinfo($sourcecm->course);
        $sectioncmids = $modinfo->sections[$destsection->section] ?? [];
        $beforemodid = null;
        $foundsource = false;
        foreach ($sectioncmids as $cmid) {
            if ($cmid == $moduleinfo->coursemodule) {
                continue;
            }
            if ($foundsource) {
                $beforemodid = $cmid;
                break;
            }
            if ($cmid == $sourcecmid) {
                $foundsource = true;
            }
        }

        $newcm = $DB->get_record('course_modules', ['id' => $moduleinfo->coursemodule], '*', MUST_EXIST);
        $beforemod = $beforemodid
            ? $DB->get_record('course_modules', ['id' => $beforemodid], '*', MUST_EXIST)
            : null;

        moveto_module($newcm, $destsection, $beforemod);

        set_coursemodule_visible($sourcecmid, 0);

        rebuild_course_cache($sourcecm->course, true);
    }

    return $moduleinfo;
}

/**
 * Updates an existing instance of mod_aiproofreader.
 *
 * @param stdClass $aiproofreader
 * @param mod_aiproofreader_mod_form|null $mform
 * @return bool
 */
function aiproofreader_update_instance($aiproofreader, $mform = null) {
    global $DB;

    $aiproofreader->timemodified = time();
    $aiproofreader->id           = $aiproofreader->instance;

    if (isset($aiproofreader->aiinstructions_editor)) {
        $aiproofreader->aiinstructions       = $aiproofreader->aiinstructions_editor['text'];
        $aiproofreader->aiinstructionsformat = $aiproofreader->aiinstructions_editor['format'];
        unset($aiproofreader->aiinstructions_editor);
    }

    $result = $DB->update_record('aiproofreader', $aiproofreader);

    if ($mform) {
        $context = context_module::instance($aiproofreader->coursemodule);
        file_postupdate_standard_filemanager(
            $aiproofreader,
            'additionalfiles',
            aiproofreader_get_additionalfiles_options(),
            $context,
            'mod_aiproofreader',
            'additionalfiles',
            0
        );
    }

    aiproofreader_grade_item_update($aiproofreader);

    return $result;
}

/**
 * Deletes an instance of mod_aiproofreader and all associated student data.
 *
 * @param int $id
 * @return bool
 */
function aiproofreader_delete_instance($id) {
    global $DB;

    if (!$aiproofreader = $DB->get_record('aiproofreader', ['id' => $id])) {
        return false;
    }

    $submissionids = $DB->get_fieldset_select('aiproofreader_submission', 'id', 'aiproofreaderid = ?', [$id]);

    if (!empty($submissionids)) {
        [$insql, $inparams] = $DB->get_in_or_equal($submissionids);
        $DB->delete_records_select('aiproofreader_studentsurvey', "submissionid $insql", $inparams);
        $DB->delete_records_select('aiproofreader_teachersurvey', "submissionid $insql", $inparams);
        $DB->delete_records_select('aiproofreader_grade', "submissionid $insql", $inparams);
    }

    $DB->delete_records('aiproofreader_submission', ['aiproofreaderid' => $id]);
    $DB->delete_records('aiproofreader', ['id' => $id]);

    // Remove any stored submission files (draft/final Word documents).
    if ($cm = get_coursemodule_from_instance('aiproofreader', $id)) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_aiproofreader');
    }

    aiproofreader_grade_item_update($aiproofreader, 'reset');

    return true;
}

/**
 * Creates or updates the gradebook item for a mod_aiproofreader instance.
 *
 * @param stdClass $aiproofreader
 * @param array|stdClass|string|null $grades
 * @return int GRADE_UPDATE_OK or error code
 */
function aiproofreader_grade_item_update($aiproofreader, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => clean_param($aiproofreader->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax'  => $aiproofreader->grade,
        'grademin'  => 0,
    ];

    if (isset($aiproofreader->gradecat)) {
        $params['categoryid'] = $aiproofreader->gradecat;
    }

    if (isset($aiproofreader->gradepass) && $aiproofreader->gradepass !== '') {
        $params['gradepass'] = $aiproofreader->gradepass;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update(
        'mod/aiproofreader',
        $aiproofreader->course,
        'mod',
        'aiproofreader',
        $aiproofreader->id,
        0,
        $grades,
        $params
    );
}

/**
 * Pushes teacher-entered grades from aiproofreader_grade into the gradebook.
 *
 * @param stdClass $aiproofreader
 * @param int $userid 0 = all students
 * @param bool $nullifnone
 */
function aiproofreader_update_grades($aiproofreader, $userid = 0, $nullifnone = true) {
    global $DB;

    if ($userid) {
        $sql = "SELECT s.userid, g.grade, g.timemodified
                  FROM {aiproofreader_submission} s
                  JOIN {aiproofreader_grade} g ON g.submissionid = s.id
                 WHERE s.aiproofreaderid = ? AND s.userid = ?";
        $params = [$aiproofreader->id, $userid];
    } else {
        $sql = "SELECT s.userid, g.grade, g.timemodified
                  FROM {aiproofreader_submission} s
                  JOIN {aiproofreader_grade} g ON g.submissionid = s.id
                 WHERE s.aiproofreaderid = ?";
        $params = [$aiproofreader->id];
    }

    $records = $DB->get_records_sql($sql, $params);

    $grades = [];
    foreach ($records as $record) {
        $grade = new stdClass();
        $grade->userid     = $record->userid;
        $grade->rawgrade   = $record->grade;
        $grade->dategraded = $record->timemodified;
        $grades[$record->userid] = $grade;
    }

    if ($userid && empty($grades) && $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        aiproofreader_grade_item_update($aiproofreader, $grade);
    } else if ($grades) {
        aiproofreader_grade_item_update($aiproofreader, $grades);
    } else {
        aiproofreader_grade_item_update($aiproofreader);
    }
}

/**
 * Adds the AI Proofreader reset option to the course reset form.
 *
 * @param MoodleQuickForm $mform
 */
function aiproofreader_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'aiproofreaderheader', get_string('pluginname', 'aiproofreader'));
    $mform->addElement(
        'checkbox',
        'reset_aiproofreader_submissions',
        get_string('resetsubmissions', 'aiproofreader')
    );
}

/**
 * Default values for the course reset form.
 *
 * @param stdClass $course
 * @return array
 */
function aiproofreader_reset_course_form_defaults($course) {
    return ['reset_aiproofreader_submissions' => 1];
}

/**
 * Deletes all student submissions, surveys, and grades for a course reset.
 *
 * @param stdClass $data
 * @return array
 */
function aiproofreader_reset_userdata($data) {
    global $DB;

    $status = [];
    $componentstr = get_string('pluginname', 'aiproofreader');

    if (!empty($data->reset_aiproofreader_submissions)) {
        $instanceids = $DB->get_fieldset_select('aiproofreader', 'id', 'course = ?', [$data->courseid]);

        if (!empty($instanceids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($instanceids);

            $submissionids = $DB->get_fieldset_select(
                'aiproofreader_submission',
                'id',
                "aiproofreaderid $insql",
                $inparams
            );

            if (!empty($submissionids)) {
                [$subsql, $subparams] = $DB->get_in_or_equal($submissionids);
                $DB->delete_records_select('aiproofreader_studentsurvey', "submissionid $subsql", $subparams);
                $DB->delete_records_select('aiproofreader_teachersurvey', "submissionid $subsql", $subparams);
                $DB->delete_records_select('aiproofreader_grade', "submissionid $subsql", $subparams);

                // Remove stored submission files for each instance in this course.
                $fs = get_file_storage();
                foreach ($instanceids as $instanceid) {
                    if ($cm = get_coursemodule_from_instance('aiproofreader', $instanceid)) {
                        $context = context_module::instance($cm->id);
                        $fs->delete_area_files($context->id, 'mod_aiproofreader');
                    }
                }
            }

            $DB->delete_records_select('aiproofreader_submission', "aiproofreaderid $insql", $inparams);
        }

        $status[] = [
            'component' => $componentstr,
            'item'      => get_string('resetsubmissions', 'aiproofreader'),
            'error'     => false,
        ];
    }

    return $status;
}

/**
 * Serves files from the draftsubmission and finalsubmission file areas.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function mod_aiproofreader_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if (!in_array($filearea, ['draftsubmission', 'finalsubmission', 'additionalfiles'])) {
        return false;
    }

    require_login($course, false, $cm);

    if ($filearea === 'additionalfiles') {
        require_capability('mod/aiproofreader:view', $context);

        $itemid = array_shift($args); // Always 0.
        $filename = array_pop($args);
        $filepath = '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_aiproofreader', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 86400, 0, $forcedownload, $options);
        return;
    }

    $itemid = array_shift($args);

    $submission = $DB->get_record('aiproofreader_submission', ['id' => $itemid], '*', MUST_EXIST);

    // Only the owning student, or a grader, may download these files.
    if (
        $submission->userid != $USER->id
            && !has_capability('mod/aiproofreader:viewallsubmissions', $context)
            && !has_capability('mod/aiproofreader:grade', $context)
    ) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_aiproofreader', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
