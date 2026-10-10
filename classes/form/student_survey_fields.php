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

/**
 * Student survey fields for a moodleform.
 */
trait student_survey_fields {
    /**
     * Question names that use the shared 1-5 radio scale.
     *
     * @return string[]
     */
    protected static function student_scale_questions() {
        return ['q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback', 'q5confidence'];
    }

    /**
     * Adds the student survey - only when the site has the survey system
     * turned on; individual questions can be turned off too.
     */
    protected function add_student_survey_fields() {
        $mform = $this->_form;

        if (aiproofreader_survey_enabled()) {
            $mform->addElement('header', 'surveyheader', get_string('surveyheading', 'aiproofreader'));
            $mform->setExpanded('surveyheader', true);

            foreach (['q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback'] as $qkey) {
                if (aiproofreader_question_enabled('student', $qkey)) {
                    $this->add_scale_radios($qkey, aiproofreader_question_text('student', $qkey));
                }
            }

            if (aiproofreader_question_enabled('student', 'q4categoryhelped')) {
                $categorygroup = [
                    $mform->createElement(
                        'radio',
                        'q4categoryhelped',
                        '',
                        get_string('q4categoryhelped_grammar', 'aiproofreader'),
                        'grammar'
                    ),
                    $mform->createElement(
                        'radio',
                        'q4categoryhelped',
                        '',
                        get_string('q4categoryhelped_assignment', 'aiproofreader'),
                        'assignment'
                    ),
                    $mform->createElement(
                        'radio',
                        'q4categoryhelped',
                        '',
                        get_string('q4categoryhelped_both', 'aiproofreader'),
                        'both'
                    ),
                ];
                $mform->addGroup(
                    $categorygroup,
                    'q4categoryhelped_group',
                    aiproofreader_question_text('student', 'q4categoryhelped'),
                    [' '],
                    false
                );
                $mform->setType('q4categoryhelped', PARAM_ALPHA);
            }

            if (aiproofreader_question_enabled('student', 'q5confidence')) {
                $this->add_scale_radios('q5confidence', aiproofreader_question_text('student', 'q5confidence'));
            }

            if (aiproofreader_question_enabled('student', 'freetext')) {
                $mform->addElement(
                    'textarea',
                    'freetext',
                    get_string('freetextlabel', 'aiproofreader'),
                    ['rows' => 3, 'cols' => 60]
                );
                $mform->setType('freetext', PARAM_TEXT);
            }
        }
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
        $group[] = $mform->createElement('static', $name . '_low', '', get_string('scalelabellow', 'aiproofreader'));
        for ($i = 1; $i <= 5; $i++) {
            $group[] = $mform->createElement('radio', $name, '', $i, $i);
        }
        $group[] = $mform->createElement('static', $name . '_high', '', get_string('scalelabelhigh', 'aiproofreader'));
        $mform->addGroup($group, $name . '_group', $label, [' '], false);
        $mform->setType($name, PARAM_INT);
    }

    /**
     * Checks that every required survey question was answered.
     *
     * @param array $data
     * @return array Errors keyed by element name
     */
    protected function validate_student_survey($data) {
        $errors = [];
        if (!aiproofreader_survey_enabled()) {
            return $errors;
        }

        foreach (static::student_scale_questions() as $question) {
            if (aiproofreader_question_enabled('student', $question) && empty($data[$question])) {
                $errors[$question . '_group'] = get_string('err_surveyrequired', 'aiproofreader');
            }
        }

        if (aiproofreader_question_enabled('student', 'q4categoryhelped') && empty($data['q4categoryhelped'])) {
            $errors['q4categoryhelped_group'] = get_string('err_surveyrequired', 'aiproofreader');
        }

        return $errors;
    }
}
