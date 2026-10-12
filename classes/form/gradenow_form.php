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
 * "Grade now" form: a grade and instructor comments for a student who has
 * not made a final submission yet. There is no teacher survey here, since
 * there is no submitted work to judge.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Grade now form.
 */
class gradenow_form extends \moodleform {
    /**
     * Defines the grade and comments fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $aiproofreader = $this->_customdata['aiproofreader'];

        $mform->addElement('header', 'gradeheader', get_string('gradeheader', 'aiproofreader'));
        $mform->setExpanded('gradeheader', true);

        $mform->addElement(
            'float',
            'grade',
            get_string('gradeoutof', 'aiproofreader', aiproofreader_format_grade($aiproofreader, $aiproofreader->grade)),
            ['size' => '6']
        );
        $mform->addRule('grade', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'instructorcomments_editor',
            get_string('instructorcomments', 'aiproofreader'),
            ['rows' => 6],
            ['maxfiles' => 0, 'noclean' => false, 'trusttext' => false]
        );
        $mform->setType('instructorcomments_editor', PARAM_RAW);

        $this->add_action_buttons(true, get_string('savegrade', 'aiproofreader'));
    }

    /**
     * Validates the grade is within range.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $aiproofreader = $this->_customdata['aiproofreader'];
        $max = (float) $aiproofreader->grade;

        // The float element has already turned the entry into a number (or
        // false if it isn't one), honouring the user's decimal separator.
        $grade = $data['grade'] ?? null;
        if (!is_numeric($grade) || $grade < 0 || $grade > $max) {
            $errors['grade'] = get_string(
                'err_gradeoutofrange',
                'aiproofreader',
                aiproofreader_format_grade($aiproofreader, $max)
            );
        }

        return $errors;
    }
}
