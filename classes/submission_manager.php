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
 * Core submission workflow logic for mod_aiproofreader: draft -> AI feedback ->
 * final submission (with required survey) -> AI comparison -> ready for grading.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;


/**
 * Core submission workflow logic: draft, AI feedback, final submission, grading.
 */
class submission_manager {
    /** @var array Per-request cache of fetched Google Doc text, keyed by URL. */
    protected static $gdrivetextcache = [];

    /**
     * Fetches the student's submission record for this instance, creating an
     * empty draft-status row the first time they visit.
     *
     * @param int $aiproofreaderid
     * @param int $userid
     * @return \stdClass
     */
    public static function get_or_create($aiproofreaderid, $userid) {
        global $DB;

        $submission = $DB->get_record(
            'aiproofreader_submission',
            ['aiproofreaderid' => $aiproofreaderid, 'userid' => $userid]
        );

        if ($submission) {
            return $submission;
        }

        $submission = new \stdClass();
        $submission->aiproofreaderid = $aiproofreaderid;
        $submission->userid = $userid;
        $submission->status = 'draft';
        $submission->timecreated = time();
        $submission->timemodified = time();
        $submission->id = $DB->insert_record('aiproofreader_submission', $submission);

        return $DB->get_record('aiproofreader_submission', ['id' => $submission->id]);
    }

    /**
     * Whether a file has been added to the given draft file area.
     *
     * @param int $draftitemid
     * @return bool
     */
    protected static function draft_area_has_files($draftitemid) {
        if (empty($draftitemid)) {
            return false;
        }
        $files = file_get_drafarea_files($draftitemid);
        return !empty($files) && !empty($files->list);
    }

    /**
     * Saves the student's draft, then generates AI feedback for it.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission
     * @param \context_module $context
     * @param string $type text|file|gdrive
     * @param string $text
     * @param string $gdrivelink
     * @param int $draftfileitemid Draft area item id from the filepicker, if type is file
     * @param int $gdrivefileitemid Draft area item id from the Google Doc filepicker, if type is gdrive
     * @return \stdClass The updated submission record
     */
    public static function save_draft(
        $aiproofreader,
        $submission,
        \context_module $context,
        $type,
        $text,
        $gdrivelink,
        $draftfileitemid,
        $gdrivefileitemid
    ) {
        global $DB;

        $submission->initialsubmissiontype = $type;
        $submission->initialtext = null;
        $submission->initialgdrivelink = null;

        if ($type === 'text') {
            $submission->initialtext = $text;
        } else if ($type === 'gdrive') {
            if (self::draft_area_has_files($gdrivefileitemid)) {
                file_save_draft_area_files(
                    $gdrivefileitemid,
                    $context->id,
                    'mod_aiproofreader',
                    'draftgdrivefile',
                    $submission->id,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
                $submission->initialtext = self::extract_text_from_stored_file($context, $submission->id, 'draftgdrivefile');
            } else {
                $submission->initialgdrivelink = $gdrivelink;
                $submission->initialtext = self::fetch_gdrive_text($gdrivelink);
            }
        } else if ($type === 'file') {
            file_save_draft_area_files(
                $draftfileitemid,
                $context->id,
                'mod_aiproofreader',
                'draftsubmission',
                $submission->id,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $submission->initialtext = self::extract_text_from_stored_file($context, $submission->id, 'draftsubmission');
        }

        $submission->initialtimesubmitted = time();
        $submission->status = 'feedbackpending';
        $submission->timemodified = time();
        $DB->update_record('aiproofreader_submission', $submission);

        // AI feedback is generated separately via the async AJAX call
        // (classes/external/generate_feedback.php) so this save stays fast
        // and never blocks on the AI server.
        return $DB->get_record('aiproofreader_submission', ['id' => $submission->id]);
    }

    /**
     * Calls the AI to generate Grammar/Spelling + Assignment Specifics feedback
     * for the student's draft text.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission Modified in place and saved
     * @return string Error message if the call was not successful, empty string on success
     */
    public static function generate_feedback($aiproofreader, $submission) {
        global $DB;

        if (empty($submission->initialtext)) {
            $errormessage = ($submission->initialsubmissiontype === 'gdrive')
                ? get_string('gdrivefetchfailed', 'aiproofreader')
                : get_string('aiunknownerror', 'aiproofreader');
            debugging('mod_aiproofreader: no draft text available for submission ' . $submission->id
                . ' (type: ' . $submission->initialsubmissiontype . ')', DEBUG_DEVELOPER);
            $submission->timemodified = time();
            $DB->update_record('aiproofreader_submission', $submission);
            return $errormessage;
        }

        $cm = get_coursemodule_from_instance('aiproofreader', $aiproofreader->id, $aiproofreader->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $lexile = aiproofreader_get_lexile_for_grade((int)$aiproofreader->gradelevel);

        $defaultinstructions = get_config('aiproofreader', 'defaultaiinstructions');
        if ($defaultinstructions === false || trim((string)$defaultinstructions) === '') {
            $defaultinstructions = get_string('defaultaiinstructions_default', 'aiproofreader');
        }

        $prompt = get_string('aiprompt_feedback', 'aiproofreader', (object) [
            'defaultinstructions' => $defaultinstructions,
            'gradelevel' => $aiproofreader->gradelevel,
            'lexile' => $lexile,
            'activityinstructions' => !empty($aiproofreader->intro) ? strip_tags($aiproofreader->intro) : '',
            'aiinstructions' => !empty($aiproofreader->aiinstructions) ? strip_tags($aiproofreader->aiinstructions) : '',
            'studenttext' => $submission->initialtext,
        ]);

        $errormessage = '';

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $submission->userid,
                prompttext: $prompt
            );
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $raw = $response->get_response_data()['generatedcontent'] ?? '';
                [$grammar, $assignment] = self::parse_feedback_sections($raw);

                $submission->feedbackgrammar = $grammar;
                $submission->feedbackassignment = $assignment;
                $submission->feedbacktimecreated = time();
                $submission->feedbackaimodel = aiproofreader_build_ai_model_label($response);
                $submission->status = 'feedbackready';
            } else {
                // The AI subsystem responded but declined/failed the request - capture why.
                $errormessage = $response->get_errormessage();
                if (empty($errormessage)) {
                    $errormessage = get_string('aiunknownerror', 'aiproofreader');
                }
                debugging('mod_aiproofreader: AI feedback generation unsuccessful: ' . $errormessage, DEBUG_DEVELOPER);
            }
        } catch (\Throwable $e) {
            $errormessage = $e->getMessage();
            debugging('mod_aiproofreader: AI feedback generation failed: ' . $errormessage, DEBUG_DEVELOPER);
        }

        $submission->timemodified = time();
        $DB->update_record('aiproofreader_submission', $submission);

        return $errormessage;
    }

    /**
     * Splits the AI's raw response into the two feedback categories.
     *
     * @param string $raw
     * @return array [grammar, assignment]
     */
    protected static function parse_feedback_sections($raw) {
        $grammar = '';
        $assignment = '';

        if (preg_match('/GRAMMAR AND SPELLING:?\s*(.*?)\s*ASSIGNMENT SPECIFICS:?\s*(.*)/is', $raw, $matches)) {
            $grammar = trim($matches[1]);
            $assignment = trim($matches[2]);
        } else {
            // AI did not follow the requested format; show it all as assignment feedback
            // rather than silently dropping content.
            $assignment = trim($raw);
        }

        return [$grammar, $assignment];
    }

    /**
     * Splits the AI's comparison response into the 1-5 followed-score and
     * the narrative summary for the teacher.
     *
     * @param string $raw
     * @return array [score (int|null), summary (string)]
     */
    protected static function parse_comparison_response($raw) {
        $score = null;
        $summary = trim($raw);

        if (preg_match('/FOLLOWED SCORE:?\s*(\d)/i', $raw, $matches)) {
            $parsed = (int)$matches[1];
            if ($parsed >= 1 && $parsed <= 5) {
                $score = $parsed;
            }
        }

        if (preg_match('/SUMMARY:?\s*(.*)/is', $raw, $matches)) {
            $summary = trim($matches[1]);
        }

        return [$score, $summary];
    }

    /**
     * Saves the final submission and the required student survey together,
     * then triggers the AI draft-vs-final comparison and activity completion.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission
     * @param \context_module $context
     * @param string $type
     * @param string $text
     * @param string $gdrivelink
     * @param int $finalfileitemid
     * @param int $gdrivefileitemid Draft area item id from the Google Doc filepicker, if type is gdrive
     * @param array $surveydata
     * @return \stdClass The updated submission record
     */
    public static function submit_final(
        $aiproofreader,
        $submission,
        \context_module $context,
        $type,
        $text,
        $gdrivelink,
        $finalfileitemid,
        $gdrivefileitemid,
        array $surveydata
    ) {
        global $DB;

        $submission->finalsubmissiontype = $type;
        $submission->finaltext = null;
        $submission->finalgdrivelink = null;

        if ($type === 'text') {
            $submission->finaltext = $text;
        } else if ($type === 'gdrive') {
            if (self::draft_area_has_files($gdrivefileitemid)) {
                file_save_draft_area_files(
                    $gdrivefileitemid,
                    $context->id,
                    'mod_aiproofreader',
                    'finalgdrivefile',
                    $submission->id,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
                $submission->finaltext = self::extract_text_from_stored_file($context, $submission->id, 'finalgdrivefile');
            } else {
                $submission->finalgdrivelink = $gdrivelink;
                $submission->finaltext = self::fetch_gdrive_text($gdrivelink);
            }
        } else if ($type === 'file') {
            file_save_draft_area_files(
                $finalfileitemid,
                $context->id,
                'mod_aiproofreader',
                'finalsubmission',
                $submission->id,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $submission->finaltext = self::extract_text_from_stored_file($context, $submission->id, 'finalsubmission');
        }

        $submission->finaltimesubmitted = time();
        $submission->status = 'finalsubmitted';
        $submission->timemodified = time();
        $DB->update_record('aiproofreader_submission', $submission);

        self::save_student_survey($submission, $surveydata);
        self::update_completion($aiproofreader, $submission);

        // Generated synchronously (not via the async AJAX trigger used for
        // draft feedback) so it's always present the moment anyone - student
        // or teacher - views this submission afterward. Final submission
        // happens once per student, so the concurrent-load concern that
        // motivated making draft feedback async doesn't apply as sharply here.
        self::generate_comparison($aiproofreader, $submission);

        // The Google Doc's text was fetched and stored so the AI feedback
        // and comparison calls above could read it - if the site hasn't
        // opted in to retaining that text, clear it back out now that both
        // AI steps are done, leaving only the drive link on the record.
        if (!aiproofreader_collect_gdrive_text()) {
            self::purge_gdrive_text_if_needed($submission);
        }

        return $DB->get_record('aiproofreader_submission', ['id' => $submission->id]);
    }

    /**
     * Clears stored Google Doc text back to null for any gdrive-type field
     * on this submission, leaving the drive link itself intact. No-op for
     * text/file submission types.
     *
     * @param \stdClass $submission
     */
    protected static function purge_gdrive_text_if_needed($submission) {
        global $DB;

        $changed = false;

        if (
            $submission->initialsubmissiontype === 'gdrive'
            && $submission->initialtext !== null
            && !empty($submission->initialgdrivelink)
        ) {
            $submission->initialtext = null;
            $changed = true;
        }
        if (
            $submission->finalsubmissiontype === 'gdrive'
            && $submission->finaltext !== null
            && !empty($submission->finalgdrivelink)
        ) {
            $submission->finaltext = null;
            $changed = true;
        }

        if ($changed) {
            $submission->timemodified = time();
            $DB->update_record('aiproofreader_submission', $submission);
        }
    }

    /**
     * Saves the student's survey answers for a submission.
     *
     * @param \stdClass $submission
     * @param array $data
     */
    protected static function save_student_survey($submission, array $data) {
        global $DB;

        if (!aiproofreader_survey_enabled()) {
            return;
        }

        if ($DB->record_exists('aiproofreader_studentsurvey', ['submissionid' => $submission->id])) {
            return;
        }

        $survey = new \stdClass();
        $survey->submissionid = $submission->id;
        $survey->q1overallfeedback = self::survey_value('student', 'q1overallfeedback', $data);
        $survey->q2specificfeedback = self::survey_value('student', 'q2specificfeedback', $data);
        $survey->q3usedfeedback = self::survey_value('student', 'q3usedfeedback', $data);
        $survey->q4categoryhelped = self::survey_value('student', 'q4categoryhelped', $data);
        $survey->q5confidence = self::survey_value('student', 'q5confidence', $data);
        $survey->freetext = aiproofreader_question_enabled('student', 'freetext') ? ($data['freetext'] ?? '') : null;
        $survey->timecreated = time();

        $DB->insert_record('aiproofreader_studentsurvey', $survey);
    }

    /**
     * Returns a survey answer only if that question is currently enabled,
     * otherwise null so it's stored as "not collected" rather than a stale
     * or fabricated value.
     *
     * @param string $surveytype student|teacher
     * @param string $qkey
     * @param array $data
     * @return int|string|null
     */
    protected static function survey_value($surveytype, $qkey, array $data) {
        if (!aiproofreader_question_enabled($surveytype, $qkey)) {
            return null;
        }
        if (!isset($data[$qkey]) || $data[$qkey] === null || $data[$qkey] === '') {
            return null;
        }
        return $qkey === 'q4categoryhelped' ? $data[$qkey] : (int) $data[$qkey];
    }

    /**
     * Calls the AI to compare the draft, the feedback given, and the final
     * version, for the teacher to review during grading.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission
     * @return string Error message if the call was not successful, empty string on success
     */
    public static function generate_comparison($aiproofreader, $submission) {
        global $DB;

        if (empty($submission->initialtext) || empty($submission->finaltext)) {
            debugging('mod_aiproofreader: skipping comparison for submission ' . $submission->id
                . ' - draft or final text unavailable', DEBUG_DEVELOPER);
            return get_string('aiunknownerror', 'aiproofreader');
        }

        $cm = get_coursemodule_from_instance('aiproofreader', $aiproofreader->id, $aiproofreader->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $prompt = get_string('aiprompt_comparison', 'aiproofreader', (object) [
            'gradelevel' => $aiproofreader->gradelevel,
            'activityinstructions' => !empty($aiproofreader->intro) ? strip_tags($aiproofreader->intro) : '',
            'draft' => $submission->initialtext,
            'feedbackgrammar' => $submission->feedbackgrammar,
            'feedbackassignment' => $submission->feedbackassignment,
            'final' => $submission->finaltext,
        ]);

        $errormessage = '';

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $submission->userid,
                prompttext: $prompt
            );
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $raw = $response->get_response_data()['generatedcontent'] ?? '';
                [$score, $summary] = self::parse_comparison_response($raw);

                $submission->aifollowedscore = $score;
                $submission->aicomparison = $summary;
                $submission->aicomparisontimecreated = time();
                $submission->comparisonaimodel = aiproofreader_build_ai_model_label($response);
            } else {
                $errormessage = $response->get_errormessage();
                if (empty($errormessage)) {
                    $errormessage = get_string('aiunknownerror', 'aiproofreader');
                }
                debugging('mod_aiproofreader: AI comparison generation unsuccessful: ' . $errormessage, DEBUG_DEVELOPER);
            }
        } catch (\Throwable $e) {
            $errormessage = $e->getMessage();
            // Comparison is a nice-to-have for the grader; grading can proceed without it.
            debugging('mod_aiproofreader: AI comparison generation failed: ' . $errormessage, DEBUG_DEVELOPER);
        }

        $submission->timemodified = time();
        $DB->update_record('aiproofreader_submission', $submission);

        return $errormessage;
    }

    /**
     * Marks the activity complete for this student, if completion is enabled.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission
     */
    protected static function update_completion($aiproofreader, $submission) {
        $cm = get_coursemodule_from_instance('aiproofreader', $aiproofreader->id);
        if (!$cm) {
            return;
        }

        $cminfo = \cm_info::create($cm);
        if ($cminfo->completion == COMPLETION_TRACKING_NONE) {
            return;
        }

        $course = get_course($aiproofreader->course);
        $completion = new \completion_info($course);
        $completion->update_state($cminfo, COMPLETION_COMPLETE, $submission->userid);
    }

    /**
     * Saves the teacher's grade, instructor comments, and the required teacher
     * survey together, then pushes the grade into the gradebook. Safe to call
     * again if the teacher re-grades - existing grade/survey rows are updated.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass $submission
     * @param int $graderid
     * @param int $grade
     * @param string $comments
     * @param int $commentsformat
     * @param array $surveydata
     * @return \stdClass The updated submission record
     */
    public static function save_grade(
        $aiproofreader,
        $submission,
        $graderid,
        $grade,
        $comments,
        $commentsformat,
        array $surveydata
    ) {
        global $DB;

        $graderecord = $DB->get_record('aiproofreader_grade', ['submissionid' => $submission->id]);
        $isnewgrade = empty($graderecord);
        if (!$graderecord) {
            $graderecord = new \stdClass();
            $graderecord->submissionid = $submission->id;
        }
        $graderecord->graderid = $graderid;
        $graderecord->grade = $grade;
        $graderecord->instructorcomments = $comments;
        $graderecord->instructorcommentsformat = $commentsformat;
        $graderecord->timemodified = time();

        if ($isnewgrade) {
            $DB->insert_record('aiproofreader_grade', $graderecord);
        } else {
            $DB->update_record('aiproofreader_grade', $graderecord);
        }

        if (aiproofreader_survey_enabled()) {
            $surveyrecord = $DB->get_record('aiproofreader_teachersurvey', ['submissionid' => $submission->id]);
            $isnewsurvey = empty($surveyrecord);
            if (!$surveyrecord) {
                $surveyrecord = new \stdClass();
                $surveyrecord->submissionid = $submission->id;
            }
            $surveyrecord->graderid = $graderid;
            $surveyrecord->q1overallfeedback = self::survey_value('teacher', 'q1overallfeedback', $surveydata);
            $surveyrecord->q2specificfeedback = self::survey_value('teacher', 'q2specificfeedback', $surveydata);
            $surveyrecord->q3usedfeedback = self::survey_value('teacher', 'q3usedfeedback', $surveydata);
            $surveyrecord->q4feedbackfollowed = self::survey_value('teacher', 'q4feedbackfollowed', $surveydata);
            $surveyrecord->q5aiscaffold = self::survey_value('teacher', 'q5aiscaffold', $surveydata);
            $surveyrecord->q6aiaccuracy = self::survey_value('teacher', 'q6aiaccuracy', $surveydata);
            $surveyrecord->freetext = aiproofreader_question_enabled('teacher', 'freetext')
                ? ($surveydata['freetext'] ?? '') : null;
            $surveyrecord->timecreated = time();

            if ($isnewsurvey) {
                $DB->insert_record('aiproofreader_teachersurvey', $surveyrecord);
            } else {
                $DB->update_record('aiproofreader_teachersurvey', $surveyrecord);
            }
        }

        $submission->status = 'graded';
        $submission->timemodified = time();
        $DB->update_record('aiproofreader_submission', $submission);

        aiproofreader_update_grades($aiproofreader, $submission->userid);

        return $DB->get_record('aiproofreader_submission', ['id' => $submission->id]);
    }

    /**
     * Fetches the plain-text content of a Google Doc via its public export
     * endpoint. Only works if the doc is shared as "Anyone with the link
     * can view" (or comment/edit) - a private doc will return a Google
     * sign-in page instead of the content, which this detects and treats
     * as a failure rather than silently feeding garbage to the AI.
     *
     * Public (and cached per URL) so form validation can check readability
     * before accepting a submission, without fetching the same doc twice.
     *
     * @param string $url
     * @return string|null The document text, or null if it could not be read
     */
    public static function fetch_gdrive_text($url) {
        if (array_key_exists($url, self::$gdrivetextcache)) {
            return self::$gdrivetextcache[$url];
        }

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if (!preg_match('#/document/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
            self::$gdrivetextcache[$url] = null;
            return null;
        }

        $docid = $matches[1];
        $exporturl = 'https://docs.google.com/document/d/' . $docid . '/export?format=txt';

        try {
            $curl = new \curl();
            $curl->setopt([
                'CURLOPT_TIMEOUT' => 15,
                'CURLOPT_FOLLOWLOCATION' => true,
                'CURLOPT_MAXREDIRS' => 5,
            ]);
            $content = $curl->get($exporturl);
            $info = $curl->get_info();
        } catch (\Throwable $e) {
            debugging('mod_aiproofreader: Google Doc fetch failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            self::$gdrivetextcache[$url] = null;
            return null;
        }

        if (empty($content) || (isset($info['http_code']) && (int)$info['http_code'] !== 200)) {
            self::$gdrivetextcache[$url] = null;
            return null;
        }

        // A private (not link-shareable) doc returns an HTML sign-in page,
        // not the document text - detect and reject that rather than
        // treating it as real content.
        if (stripos($content, '<html') !== false || stripos($content, 'accounts.google.com') !== false) {
            self::$gdrivetextcache[$url] = null;
            return null;
        }

        $content = trim($content);
        $result = $content !== '' ? $content : null;

        self::$gdrivetextcache[$url] = $result;
        return $result;
    }

    /**
     * Reads a stored submission file and extracts its plain text.
     *
     * @param \context_module $context
     * @param int $itemid The aiproofreader_submission id (file area itemid)
     * @param string $filearea draftsubmission|finalsubmission
     * @return string
     */
    protected static function extract_text_from_stored_file(\context_module $context, $itemid, $filearea) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_aiproofreader', $filearea, $itemid, 'id', false);
        $file = reset($files);

        if (!$file) {
            return '';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'aiproofreader_');
        $file->copy_content_to($tmp);

        try {
            $text = document_parser::extract_text($tmp, $file->get_filename());
        } catch (\Throwable $e) {
            $text = '';
        } finally {
            @unlink($tmp);
        }

        return $text;
    }
}
