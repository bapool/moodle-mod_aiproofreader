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
 * Grade entry form, bundling the required teacher survey. The system will
 * not accept a grade unless the survey is answered, same as the student side.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Grade form.
 */
class grade_form extends \moodleform {
    /** @var array Question names that use the shared 1-5 radio scale. */
    protected static $scalequestions = [
        'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
        'q4feedbackfollowed', 'q5aiscaffold', 'q6aiaccuracy',
    ];

    /**
     * Defines the grade entry form fields, including the required teacher survey.
     */
    protected function definition() {
        $mform = $this->_form;
        $aiproofreader = $this->_customdata['aiproofreader'];

        $mform->addElement('header', 'gradeheader', get_string('gradeheader', 'aiproofreader'));
        $mform->setExpanded('gradeheader', true);

        $mform->addElement(
            'text',
            'grade',
            get_string('maximumgrade', 'aiproofreader') . ' (0-' . (int)$aiproofreader->grade . ')',
            ['size' => '5']
        );
        $mform->setType('grade', PARAM_INT);
        $mform->addRule('grade', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'instructorcomments_editor',
            get_string('instructorcomments', 'aiproofreader'),
            ['rows' => 8],
            ['maxfiles' => 0, 'noclean' => false, 'trusttext' => false]
        );
        $mform->setType('instructorcomments_editor', PARAM_RAW);

        $mform->addElement('header', 'teachersurveyheader', get_string('teachersurveyheading', 'aiproofreader'));
        $mform->setExpanded('teachersurveyheader', true);

        $this->add_scale_radios('q1overallfeedback', get_string('graderq1overallfeedback', 'aiproofreader'));
        $this->add_scale_radios('q2specificfeedback', get_string('graderq2specificfeedback', 'aiproofreader'));
        $this->add_scale_radios('q3usedfeedback', get_string('graderq3usedfeedback', 'aiproofreader'));
        $this->add_scale_radios('q4feedbackfollowed', get_string('graderq4feedbackfollowed', 'aiproofreader'));
        $this->add_scale_radios('q5aiscaffold', get_string('graderq5aiscaffold', 'aiproofreader'));
        $this->add_scale_radios('q6aiaccuracy', get_string('graderq6aiaccuracy', 'aiproofreader'));

        $mform->addElement(
            'textarea',
            'freetext',
            get_string('teacherfreetextlabel', 'aiproofreader'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('freetext', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savegrade', 'aiproofreader'));
    }

    /**
     * Adds a 1-5 radio button group for one survey question.
     *
     * @param string $name
     * @param string $label
     */
    protected function add_scale_radios($name, $label) {
        $mform = $this->_form;
        $group = [];
        for ($i = 1; $i <= 5; $i++) {
            $group[] = $mform->createElement('radio', $name, '', $i, $i);
        }
        $mform->addGroup($group, $name . '_group', $label, [' '], false);
        $mform->setType($name, PARAM_INT);
    }

    /**
     * Validates the submitted grade and survey answers.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $aiproofreader = $this->_customdata['aiproofreader'];

        if (
            $data['grade'] === '' || !is_numeric($data['grade'])
                || (int)$data['grade'] < 0 || (int)$data['grade'] > (int)$aiproofreader->grade
        ) {
            $errors['grade'] = get_string('err_gradeoutofrange', 'aiproofreader', (int)$aiproofreader->grade);
        }

        foreach (static::$scalequestions as $question) {
            if (empty($data[$question])) {
                $errors[$question . '_group'] = get_string('err_surveyrequired', 'aiproofreader');
            }
        }

        return $errors;
    }
}
