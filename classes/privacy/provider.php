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
 * Privacy API implementation for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for mod_aiproofreader.
 *
 * A student's submission (draft, final, AI feedback, AI comparison, and
 * their survey answers) belongs to that student and is fully exported and
 * deleted with them. A teacher's grade and teacher-survey answers on a
 * submission are that student's data too (they describe the student's
 * work), so deleting a grader only removes their identifying reference
 * (graderid) rather than the underlying grade - the student's grade and
 * comments stay intact, just no longer attributed to a specific grader.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes what personal data this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'aiproofreader_submission',
            [
                'userid' => 'privacy:metadata:aiproofreader_submission:userid',
                'status' => 'privacy:metadata:aiproofreader_submission:status',
                'initialtext' => 'privacy:metadata:aiproofreader_submission:initialtext',
                'initialgdrivelink' => 'privacy:metadata:aiproofreader_submission:initialgdrivelink',
                'initialtimesubmitted' => 'privacy:metadata:aiproofreader_submission:initialtimesubmitted',
                'feedbackgrammar' => 'privacy:metadata:aiproofreader_submission:feedbackgrammar',
                'feedbackassignment' => 'privacy:metadata:aiproofreader_submission:feedbackassignment',
                'finaltext' => 'privacy:metadata:aiproofreader_submission:finaltext',
                'finalgdrivelink' => 'privacy:metadata:aiproofreader_submission:finalgdrivelink',
                'finaltimesubmitted' => 'privacy:metadata:aiproofreader_submission:finaltimesubmitted',
                'aicomparison' => 'privacy:metadata:aiproofreader_submission:aicomparison',
                'aifollowedscore' => 'privacy:metadata:aiproofreader_submission:aifollowedscore',
                'timecreated' => 'privacy:metadata:aiproofreader_submission:timecreated',
            ],
            'privacy:metadata:aiproofreader_submission'
        );

        $collection->add_database_table(
            'aiproofreader_studentsurvey',
            [
                'q1overallfeedback' => 'privacy:metadata:aiproofreader_studentsurvey:q1overallfeedback',
                'q2specificfeedback' => 'privacy:metadata:aiproofreader_studentsurvey:q2specificfeedback',
                'q3usedfeedback' => 'privacy:metadata:aiproofreader_studentsurvey:q3usedfeedback',
                'q4categoryhelped' => 'privacy:metadata:aiproofreader_studentsurvey:q4categoryhelped',
                'q5confidence' => 'privacy:metadata:aiproofreader_studentsurvey:q5confidence',
                'freetext' => 'privacy:metadata:aiproofreader_studentsurvey:freetext',
                'timecreated' => 'privacy:metadata:aiproofreader_studentsurvey:timecreated',
            ],
            'privacy:metadata:aiproofreader_studentsurvey'
        );

        $collection->add_database_table(
            'aiproofreader_teachersurvey',
            [
                'graderid' => 'privacy:metadata:aiproofreader_teachersurvey:graderid',
                'q1overallfeedback' => 'privacy:metadata:aiproofreader_teachersurvey:q1overallfeedback',
                'q2specificfeedback' => 'privacy:metadata:aiproofreader_teachersurvey:q2specificfeedback',
                'q3usedfeedback' => 'privacy:metadata:aiproofreader_teachersurvey:q3usedfeedback',
                'q4feedbackfollowed' => 'privacy:metadata:aiproofreader_teachersurvey:q4feedbackfollowed',
                'q5aiscaffold' => 'privacy:metadata:aiproofreader_teachersurvey:q5aiscaffold',
                'q6aiaccuracy' => 'privacy:metadata:aiproofreader_teachersurvey:q6aiaccuracy',
                'freetext' => 'privacy:metadata:aiproofreader_teachersurvey:freetext',
                'timecreated' => 'privacy:metadata:aiproofreader_teachersurvey:timecreated',
            ],
            'privacy:metadata:aiproofreader_teachersurvey'
        );

        $collection->add_database_table(
            'aiproofreader_grade',
            [
                'graderid' => 'privacy:metadata:aiproofreader_grade:graderid',
                'grade' => 'privacy:metadata:aiproofreader_grade:grade',
                'instructorcomments' => 'privacy:metadata:aiproofreader_grade:instructorcomments',
                'timemodified' => 'privacy:metadata:aiproofreader_grade:timemodified',
            ],
            'privacy:metadata:aiproofreader_grade'
        );

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_subsystem_link('core_ai', [], 'privacy:metadata:core_ai');

        return $collection;
    }

    /**
     * Gets the list of contexts containing personal data for a user, either as the
     * submitting student or as a grader.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {aiproofreader} ap ON ap.id = cm.instance
                  JOIN {aiproofreader_submission} s ON s.aiproofreaderid = ap.id
             LEFT JOIN {aiproofreader_grade} g ON g.submissionid = s.id
             LEFT JOIN {aiproofreader_teachersurvey} ts ON ts.submissionid = s.id
                 WHERE s.userid = :userid1 OR g.graderid = :userid2 OR ts.graderid = :userid3";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'aiproofreader',
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Gets the list of users with personal data in a given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('aiproofreader', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userlist->add_from_sql('userid',
            "SELECT userid FROM {aiproofreader_submission} WHERE aiproofreaderid = :instanceid",
            ['instanceid' => $cm->instance]);

        $userlist->add_from_sql('graderid',
            "SELECT g.graderid FROM {aiproofreader_submission} s
              JOIN {aiproofreader_grade} g ON g.submissionid = s.id
             WHERE s.aiproofreaderid = :instanceid",
            ['instanceid' => $cm->instance]);

        $userlist->add_from_sql('graderid',
            "SELECT ts.graderid FROM {aiproofreader_submission} s
              JOIN {aiproofreader_teachersurvey} ts ON ts.submissionid = s.id
             WHERE s.aiproofreaderid = :instanceid",
            ['instanceid' => $cm->instance]);
    }

    /**
     * Exports personal data for a user across the given approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('aiproofreader', $context->id);
            if (!$cm) {
                continue;
            }

            $submission = $DB->get_record('aiproofreader_submission',
                ['aiproofreaderid' => $cm->instance, 'userid' => $user->id]);

            if ($submission) {
                $data = (object) [
                    'status' => $submission->status,
                    'initialsubmissiontype' => $submission->initialsubmissiontype,
                    'initialtext' => $submission->initialtext,
                    'initialgdrivelink' => $submission->initialgdrivelink,
                    'initialtimesubmitted' => $submission->initialtimesubmitted
                        ? transform::datetime($submission->initialtimesubmitted) : null,
                    'feedbackgrammar' => $submission->feedbackgrammar,
                    'feedbackassignment' => $submission->feedbackassignment,
                    'finalsubmissiontype' => $submission->finalsubmissiontype,
                    'finaltext' => $submission->finaltext,
                    'finalgdrivelink' => $submission->finalgdrivelink,
                    'finaltimesubmitted' => $submission->finaltimesubmitted
                        ? transform::datetime($submission->finaltimesubmitted) : null,
                    'aicomparison' => $submission->aicomparison,
                    'aifollowedscore' => $submission->aifollowedscore,
                ];

                writer::with_context($context)->export_data([get_string('pluginname', 'mod_aiproofreader')], $data);

                writer::with_context($context)->export_area_files(
                    [get_string('pluginname', 'mod_aiproofreader')], 'mod_aiproofreader', 'draftsubmission', $submission->id);
                writer::with_context($context)->export_area_files(
                    [get_string('pluginname', 'mod_aiproofreader')], 'mod_aiproofreader', 'finalsubmission', $submission->id);

                $survey = $DB->get_record('aiproofreader_studentsurvey', ['submissionid' => $submission->id]);
                if ($survey) {
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'mod_aiproofreader'), get_string('studentsurveyheading', 'mod_aiproofreader')],
                        (object) [
                            'q1overallfeedback' => $survey->q1overallfeedback,
                            'q2specificfeedback' => $survey->q2specificfeedback,
                            'q3usedfeedback' => $survey->q3usedfeedback,
                            'q4categoryhelped' => $survey->q4categoryhelped,
                            'q5confidence' => $survey->q5confidence,
                            'freetext' => $survey->freetext,
                        ]
                    );
                }

                $grade = $DB->get_record('aiproofreader_grade', ['submissionid' => $submission->id]);
                if ($grade) {
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'mod_aiproofreader'), get_string('gradedheading', 'mod_aiproofreader')],
                        (object) [
                            'grade' => $grade->grade,
                            'instructorcomments' => $grade->instructorcomments,
                        ]
                    );
                }
            }

            // Grading actions this user performed as a teacher, on any submission in this activity.
            $sql = "SELECT g.*
                      FROM {aiproofreader_grade} g
                      JOIN {aiproofreader_submission} s ON s.id = g.submissionid
                     WHERE s.aiproofreaderid = :instanceid AND g.graderid = :graderid";
            $gradesgiven = $DB->get_records_sql($sql, ['instanceid' => $cm->instance, 'graderid' => $user->id]);

            foreach ($gradesgiven as $g) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'mod_aiproofreader'), get_string('privacy:gradesgiven', 'mod_aiproofreader')],
                    (object) [
                        'grade' => $g->grade,
                        'instructorcomments' => $g->instructorcomments,
                        'timemodified' => transform::datetime($g->timemodified),
                    ]
                );
            }
        }
    }

    /**
     * Deletes all personal data for all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('aiproofreader', $context->id);
        if (!$cm) {
            return;
        }

        $submissionids = $DB->get_fieldset_select('aiproofreader_submission', 'id', 'aiproofreaderid = ?', [$cm->instance]);

        if (!empty($submissionids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($submissionids);
            $DB->delete_records_select('aiproofreader_studentsurvey', "submissionid $insql", $inparams);
            $DB->delete_records_select('aiproofreader_teachersurvey', "submissionid $insql", $inparams);
            $DB->delete_records_select('aiproofreader_grade', "submissionid $insql", $inparams);
        }

        $DB->delete_records('aiproofreader_submission', ['aiproofreaderid' => $cm->instance]);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_aiproofreader', 'draftsubmission');
        $fs->delete_area_files($context->id, 'mod_aiproofreader', 'finalsubmission');
    }

    /**
     * Deletes personal data for one user across the given approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('aiproofreader', $context->id);
            if (!$cm) {
                continue;
            }

            $submission = $DB->get_record('aiproofreader_submission',
                ['aiproofreaderid' => $cm->instance, 'userid' => $user->id]);

            if ($submission) {
                $DB->delete_records('aiproofreader_studentsurvey', ['submissionid' => $submission->id]);
                $DB->delete_records('aiproofreader_teachersurvey', ['submissionid' => $submission->id]);
                $DB->delete_records('aiproofreader_grade', ['submissionid' => $submission->id]);
                $DB->delete_records('aiproofreader_submission', ['id' => $submission->id]);

                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'mod_aiproofreader', 'draftsubmission', $submission->id);
                $fs->delete_area_files($context->id, 'mod_aiproofreader', 'finalsubmission', $submission->id);
            }

            // Anonymize this user's grader reference on any other student's submission, rather
            // than deleting that student's grade and comments.
            $submissionids = $DB->get_fieldset_select('aiproofreader_submission', 'id', 'aiproofreaderid = ?',
                [$cm->instance]);
            if (!empty($submissionids)) {
                list($insql, $inparams) = $DB->get_in_or_equal($submissionids);
                $DB->set_field_select('aiproofreader_grade', 'graderid', 0, "graderid = ? AND submissionid $insql",
                    array_merge([$user->id], $inparams));
                $DB->set_field_select('aiproofreader_teachersurvey', 'graderid', 0, "graderid = ? AND submissionid $insql",
                    array_merge([$user->id], $inparams));
            }
        }
    }

    /**
     * Deletes personal data for multiple users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('aiproofreader', $context->id);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids);

        $submissions = $DB->get_records_select('aiproofreader_submission',
            "aiproofreaderid = ? AND userid $usersql", array_merge([$cm->instance], $userparams));

        $fs = get_file_storage();
        foreach ($submissions as $submission) {
            $DB->delete_records('aiproofreader_studentsurvey', ['submissionid' => $submission->id]);
            $DB->delete_records('aiproofreader_teachersurvey', ['submissionid' => $submission->id]);
            $DB->delete_records('aiproofreader_grade', ['submissionid' => $submission->id]);
            $fs->delete_area_files($context->id, 'mod_aiproofreader', 'draftsubmission', $submission->id);
            $fs->delete_area_files($context->id, 'mod_aiproofreader', 'finalsubmission', $submission->id);
        }

        $DB->delete_records_select('aiproofreader_submission',
            "aiproofreaderid = ? AND userid $usersql", array_merge([$cm->instance], $userparams));

        $remainingids = $DB->get_fieldset_select('aiproofreader_submission', 'id', 'aiproofreaderid = ?', [$cm->instance]);
        if (!empty($remainingids)) {
            list($subsql, $subparams) = $DB->get_in_or_equal($remainingids);
            $DB->set_field_select('aiproofreader_grade', 'graderid', 0,
                "graderid $usersql AND submissionid $subsql", array_merge($userparams, $subparams));
            $DB->set_field_select('aiproofreader_teachersurvey', 'graderid', 0,
                "graderid $usersql AND submissionid $subsql", array_merge($userparams, $subparams));
        }
    }
}
