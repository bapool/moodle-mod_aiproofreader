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
 * The final submission form, which bundles the required student survey.
 * The system will not accept a final submission unless the survey is answered.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Final submission form.
 */
class final_form extends \moodleform {
    /** @var array Question names that use the shared 1-5 radio scale. */
    protected static $scalequestions = [
        'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback', 'q5confidence',
    ];

    /**
     * Defines the final submission form fields, including the required student survey.
     */
    protected function definition() {
        $mform = $this->_form;
        $aiproofreader = $this->_customdata['aiproofreader'];

        $mform->addElement('header', 'finalcontentheader', get_string('finalsubmissionheading', 'aiproofreader'));
        $mform->setExpanded('finalcontentheader', true);

        $typeoptions = aiproofreader_enabled_type_options($aiproofreader);
        $multipletypes = (count($typeoptions) > 1);

        if ($multipletypes) {
            $mform->addElement('select', 'submissiontype', get_string('submissiontype', 'aiproofreader'), $typeoptions);
        } else {
            $only = array_key_first($typeoptions);
            $mform->addElement('hidden', 'submissiontype', $only);
            $mform->setType('submissiontype', PARAM_ALPHA);
        }

        if (!empty($aiproofreader->submtext)) {
            $mform->addElement(
                'textarea',
                'onlinetext',
                get_string('onlinetextlabel', 'aiproofreader'),
                ['rows' => 15, 'cols' => 60]
            );
            $mform->setType('onlinetext', PARAM_RAW);
            if ($multipletypes) {
                $mform->hideIf('onlinetext', 'submissiontype', 'neq', 'text');
            }
        }

        if (!empty($aiproofreader->submfile)) {
            $mform->addElement(
                'filepicker',
                'submissionfile',
                get_string('fileuploadlabel', 'aiproofreader'),
                null,
                ['maxbytes' => 10485760, 'accepted_types' => ['.doc', '.docx']]
            );
            if ($multipletypes) {
                $mform->hideIf('submissionfile', 'submissiontype', 'neq', 'file');
            }
        }

        if (!empty($aiproofreader->submgdrive)) {
            $mform->addElement('text', 'gdrivelink', get_string('gdrivelinklabel', 'aiproofreader'), ['size' => '60']);
            $mform->setType('gdrivelink', PARAM_URL);
            if ($multipletypes) {
                $mform->hideIf('gdrivelink', 'submissiontype', 'neq', 'gdrive');
            }
        }

        // Optional student survey - collected only when the site has the
        // survey system turned on; individual questions can be turned off too.
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

        $this->add_action_buttons(false, get_string('submitfinal', 'aiproofreader'));
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
     * Validates the submitted final version and survey answers.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['submissiontype'] === 'text' && trim($data['onlinetext']) === '') {
            $errors['onlinetext'] = get_string('err_notextentered', 'aiproofreader');
        }

        if ($data['submissiontype'] === 'gdrive') {
            if (empty($data['gdrivelink']) || !filter_var($data['gdrivelink'], FILTER_VALIDATE_URL)) {
                $errors['gdrivelink'] = get_string('err_invalidgdrivelink', 'aiproofreader');
            } else if (empty(\mod_aiproofreader\submission_manager::fetch_gdrive_text($data['gdrivelink']))) {
                $errors['gdrivelink'] = get_string('gdrivefetchfailed', 'aiproofreader');
            }
        }

        if ($data['submissiontype'] === 'file') {
            $draftitemid = file_get_submitted_draft_itemid('submissionfile');
            $areafiles = file_get_drafarea_files($draftitemid);
            if (empty($areafiles) || empty($areafiles->list)) {
                $errors['submissionfile'] = get_string('err_nofileuploaded', 'aiproofreader');
            }
        }

        if (aiproofreader_survey_enabled()) {
            foreach (static::$scalequestions as $question) {
                if (aiproofreader_question_enabled('student', $question) && empty($data[$question])) {
                    $errors[$question . '_group'] = get_string('err_surveyrequired', 'aiproofreader');
                }
            }

            if (aiproofreader_question_enabled('student', 'q4categoryhelped') && empty($data['q4categoryhelped'])) {
                $errors['q4categoryhelped_group'] = get_string('err_surveyrequired', 'aiproofreader');
            }
        }

        return $errors;
    }
}
