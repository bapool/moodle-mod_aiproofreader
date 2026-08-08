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
 * Restore steps for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Structure step to restore one aiproofreader activity.
 */
class restore_aiproofreader_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines structure of path elements to be processed during the restore.
     *
     * @return array of restore_path_element
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('aiproofreader', '/activity/aiproofreader');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'aiproofreader_submission',
                '/activity/aiproofreader/submissions/submission'
            );
            $paths[] = new restore_path_element(
                'aiproofreader_studentsurvey',
                '/activity/aiproofreader/submissions/submission/studentsurveys/studentsurvey'
            );
            $paths[] = new restore_path_element(
                'aiproofreader_teachersurvey',
                '/activity/aiproofreader/submissions/submission/teachersurveys/teachersurvey'
            );
            $paths[] = new restore_path_element(
                'aiproofreader_grade',
                '/activity/aiproofreader/submissions/submission/grades/grade'
            );
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the aiproofreader instance restore.
     *
     * @param array $data
     */
    protected function process_aiproofreader($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->allowsubmissionsfromdate = $this->apply_date_offset($data->allowsubmissionsfromdate);
        $data->duedate = $this->apply_date_offset($data->duedate);
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('aiproofreader', $data);
        // Immediately after inserting the record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process one student's submission restore.
     *
     * @param array $data
     */
    protected function process_aiproofreader_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->aiproofreaderid = $this->get_new_parentid('aiproofreader');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->initialtimesubmitted = $this->apply_date_offset($data->initialtimesubmitted);
        $data->feedbacktimecreated = $this->apply_date_offset($data->feedbacktimecreated);
        $data->finaltimesubmitted = $this->apply_date_offset($data->finaltimesubmitted);
        $data->aicomparisontimecreated = $this->apply_date_offset($data->aicomparisontimecreated);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('aiproofreader_submission', $data);
        // Save this mapping (with files = true) so after_execute() can restore the
        // draft/final submission files, which are keyed by the submission's own id.
        $this->set_mapping('aiproofreader_submission', $oldid, $newitemid, true);
    }

    /**
     * Process the student survey restore.
     *
     * @param array $data
     */
    protected function process_aiproofreader_studentsurvey($data) {
        global $DB;

        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('aiproofreader_submission');
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('aiproofreader_studentsurvey', $data);
    }

    /**
     * Process the teacher survey restore.
     *
     * @param array $data
     */
    protected function process_aiproofreader_teachersurvey($data) {
        global $DB;

        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('aiproofreader_submission');
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('aiproofreader_teachersurvey', $data);
    }

    /**
     * Process the grade restore.
     *
     * @param array $data
     */
    protected function process_aiproofreader_grade($data) {
        global $DB;

        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('aiproofreader_submission');
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('aiproofreader_grade', $data);
    }

    /**
     * Once the database tables have been fully restored, restore the files.
     */
    protected function after_execute() {
        // Intro editor images and the teacher's "Additional files" attachments -
        // both are stored at itemid 0, tied to the instance itself.
        $this->add_related_files('mod_aiproofreader', 'intro', null);
        $this->add_related_files('mod_aiproofreader', 'additionalfiles', null);

        // Draft/final submission files, keyed by each submission's own new id
        // via the mapping saved in process_aiproofreader_submission().
        $this->add_related_files('mod_aiproofreader', 'draftsubmission', 'aiproofreader_submission');
        $this->add_related_files('mod_aiproofreader', 'finalsubmission', 'aiproofreader_submission');
    }
}
