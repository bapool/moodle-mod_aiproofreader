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
 * The student survey fields and their validation, shared by the final
 * submission form and the survey-only form a student sees after their
 * teacher has finalized their draft ("Finalize grade").
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Student survey-only form.
 */
class survey_form extends \moodleform {
    use student_survey_fields;

    /**
     * Defines the survey fields.
     */
    protected function definition() {
        $this->add_student_survey_fields();
        $this->add_action_buttons(false, get_string('submitsurvey', 'aiproofreader'));
    }

    /**
     * Validates the survey answers.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        return parent::validation($data, $files) + $this->validate_student_survey($data);
    }
}
