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
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Backup steps for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Defines the complete aiproofreader structure for backup, with file and id annotations.
 */
class backup_aiproofreader_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure of the module.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separately.
        $aiproofreader = new backup_nested_element('aiproofreader', ['id'], [
            'name', 'intro', 'introformat',
            'allowsubmissionsfromdate', 'duedate', 'cutoffdate',
            'gradelevel', 'aiinstructions', 'aiinstructionsformat',
            'submtext', 'submfile', 'submgdrive',
            'grade', 'hidegrader', 'completionsubmit',
            'timecreated', 'timemodified']);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'status',
            'initialsubmissiontype', 'initialtext', 'initialgdrivelink', 'initialtimesubmitted',
            'feedbackgrammar', 'feedbackassignment', 'feedbacktimecreated',
            'finalsubmissiontype', 'finaltext', 'finalgdrivelink', 'finaltimesubmitted',
            'aicomparison', 'aicomparisontimecreated', 'aifollowedscore',
            'timecreated', 'timemodified']);

        $studentsurveys = new backup_nested_element('studentsurveys');
        $studentsurvey = new backup_nested_element('studentsurvey', ['id'], [
            'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
            'q4categoryhelped', 'q5confidence', 'freetext', 'timecreated']);

        $teachersurveys = new backup_nested_element('teachersurveys');
        $teachersurvey = new backup_nested_element('teachersurvey', ['id'], [
            'graderid', 'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
            'q4feedbackfollowed', 'q5aiscaffold', 'q6aiaccuracy', 'freetext', 'timecreated']);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'graderid', 'grade', 'instructorcomments', 'instructorcommentsformat', 'timemodified']);

        // Build the tree.
        $aiproofreader->add_child($submissions);
        $submissions->add_child($submission);

        $submission->add_child($studentsurveys);
        $studentsurveys->add_child($studentsurvey);

        $submission->add_child($teachersurveys);
        $teachersurveys->add_child($teachersurvey);

        $submission->add_child($grades);
        $grades->add_child($grade);

        // Define sources.
        $aiproofreader->set_source_table('aiproofreader', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $submission->set_source_table(
                'aiproofreader_submission',
                ['aiproofreaderid' => backup::VAR_PARENTID]
            );
            $studentsurvey->set_source_table(
                'aiproofreader_studentsurvey',
                ['submissionid' => backup::VAR_PARENTID]
            );
            $teachersurvey->set_source_table(
                'aiproofreader_teachersurvey',
                ['submissionid' => backup::VAR_PARENTID]
            );
            $grade->set_source_table(
                'aiproofreader_grade',
                ['submissionid' => backup::VAR_PARENTID]
            );
        }

        // Define id annotations.
        $submission->annotate_ids('user', 'userid');
        $teachersurvey->annotate_ids('user', 'graderid');
        $grade->annotate_ids('user', 'graderid');

        // Define file annotations.
        $aiproofreader->annotate_files('mod_aiproofreader', 'intro', null);
        $aiproofreader->annotate_files('mod_aiproofreader', 'additionalfiles', null);
        $submission->annotate_files('mod_aiproofreader', 'draftsubmission', 'id');
        $submission->annotate_files('mod_aiproofreader', 'finalsubmission', 'id');

        // Return the root element (aiproofreader), wrapped into standard activity structure.
        return $this->prepare_activity_structure($aiproofreader);
    }
}
