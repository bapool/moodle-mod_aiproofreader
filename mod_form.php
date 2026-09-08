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
 * The main mod_aiproofreader settings form.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The activity settings form for mod_aiproofreader.
 */
class mod_aiproofreader_mod_form extends moodleform_mod {
    /**
     * Defines the activity settings form fields.
     */
    public function definition() {
        global $CFG, $COURSE;
        require_once($CFG->libdir . '/gradelib.php');

        $mform = $this->_form;

        // Optional: prefill this form from an existing Assignment activity
        // in the course. Only relevant when adding a brand-new activity.
        if (empty($this->current->instance)) {
            $mform->addElement('header', 'importheader', get_string('importfromassign', 'aiproofreader'));
            $mform->setExpanded('importheader', false);

            $mform->addElement('static', 'importfromassigndesc', '', get_string('importfromassigndesc', 'aiproofreader'));

            $mform->addElement(
                'advcheckbox',
                'importcurrentsectiononly',
                '',
                get_string('importcurrentsectiononlylabel', 'aiproofreader')
            );
            $mform->setDefault('importcurrentsectiononly', 1);

            $mform->addElement(
                'static',
                'importcurrentsectiononlynote',
                '',
                get_string('importcurrentsectiononlynote', 'aiproofreader')
            );

            $assignoptions = [0 => get_string('choosedots')]
                + aiproofreader_get_course_assign_options($COURSE->id, $this->get_import_section_filter());
            $mform->addElement(
                'select',
                'importassigncmid',
                get_string('importfromassignlabel', 'aiproofreader'),
                $assignoptions
            );
            $mform->addHelpButton('importassigncmid', 'importfromassign', 'aiproofreader');

            $mform->registerNoSubmitButton('loadimportassign');
            $mform->addElement('submit', 'loadimportassign', get_string('loadimportassign', 'aiproofreader'));

            $mform->addElement('hidden', 'sourceassigncmid', 0);
            $mform->setType('sourceassigncmid', PARAM_INT);
        }

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assignmentname', 'aiproofreader'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description is the primary instructions shown to students AND the primary AI instructions.
        $this->standard_intro_elements(get_string('activityinstructions', 'aiproofreader'));

        // Optional teacher attachments (e.g. a lab sheet) shown to students alongside the instructions.
        $mform->addElement(
            'filemanager',
            'additionalfiles_filemanager',
            get_string('additionalfiles', 'aiproofreader'),
            null,
            aiproofreader_get_additionalfiles_options()
        );
        $mform->addHelpButton('additionalfiles_filemanager', 'additionalfiles', 'aiproofreader');

        // AI Feedback settings.
        $mform->addElement('header', 'aifeedbacksettings', get_string('aifeedbacksettings', 'aiproofreader'));
        $mform->setExpanded('aifeedbacksettings', true);

        $mform->addElement(
            'select',
            'gradelevel',
            get_string('gradelevel', 'aiproofreader'),
            aiproofreader_gradelexile_options()
        );
        $mform->addHelpButton('gradelevel', 'gradelevel', 'aiproofreader');
        $mform->setDefault('gradelevel', '9');

        $mform->addElement(
            'editor',
            'aiinstructions_editor',
            get_string('aiinstructions', 'aiproofreader'),
            ['rows' => 8],
            ['maxfiles' => 0, 'noclean' => false, 'trusttext' => false]
        );
        $mform->setType('aiinstructions_editor', PARAM_RAW);
        $mform->addHelpButton('aiinstructions_editor', 'aiinstructions', 'aiproofreader');

        // Submission types.
        $mform->addElement('header', 'submissiontypessettings', get_string('submissiontypes', 'aiproofreader'));
        $mform->setExpanded('submissiontypessettings', true);

        $mform->addElement('advcheckbox', 'submtext', get_string('submtext', 'aiproofreader'));
        $mform->setDefault('submtext', 1);

        $mform->addElement('advcheckbox', 'submfile', get_string('submfile', 'aiproofreader'));
        $mform->setDefault('submfile', 0);

        $mform->addElement('advcheckbox', 'submgdrive', get_string('submgdrive', 'aiproofreader'));
        $mform->setDefault('submgdrive', 0);

        // Availability (same convention as core Assignment).
        $mform->addElement('header', 'availability', get_string('availability', 'aiproofreader'));
        $mform->setExpanded('availability', true);

        $mform->addElement(
            'date_time_selector',
            'allowsubmissionsfromdate',
            get_string('allowsubmissionsfromdate', 'aiproofreader'),
            ['optional' => true]
        );

        $mform->addElement(
            'date_time_selector',
            'duedate',
            get_string('duedate', 'aiproofreader'),
            ['optional' => true]
        );

        $mform->addElement(
            'date_time_selector',
            'cutoffdate',
            get_string('cutoffdate', 'aiproofreader'),
            ['optional' => true]
        );
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'aiproofreader');

        // Grade.
        $mform->addElement('header', 'gradeheader', get_string('gradeheader', 'aiproofreader'));
        $mform->setExpanded('gradeheader', true);

        $mform->addElement('text', 'grade', get_string('maximumgrade', 'aiproofreader'), ['size' => '5']);
        $mform->setType('grade', PARAM_INT);
        $mform->setDefault('grade', 100);
        $mform->addRule('grade', null, 'required', null, 'client');

        $mform->addElement(
            'select',
            'gradecat',
            get_string('gradecategoryonmodform', 'grades'),
            grade_get_categories_menu($COURSE->id)
        );
        $mform->addHelpButton('gradecat', 'gradecategoryonmodform', 'grades');

        $mform->addElement('text', 'gradepass', get_string('gradepass', 'grades'));
        $mform->addHelpButton('gradepass', 'gradepass', 'grades');
        $mform->setType('gradepass', PARAM_RAW);
        $mform->setDefault('gradepass', '');

        $mform->addElement(
            'select',
            'hidegrader',
            get_string('hidegrader', 'aiproofreader'),
            [0 => get_string('no'), 1 => get_string('yes')]
        );
        $mform->addHelpButton('hidegrader', 'hidegrader', 'aiproofreader');
        $mform->setDefault('hidegrader', 0);

        // Completion, standard elements, buttons.
        $this->add_completion_rules();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * If the "Load" button next to the Assignment import picker was the
     * button just clicked, re-populates the whole form's defaults from
     * that Assignment. Left alone on every other submit (including the
     * real Save buttons), so it never silently overwrites a teacher's own
     * edits.
     */
    public function definition_after_data() {
        parent::definition_after_data();

        global $COURSE;
        $mform = $this->_form;

        if (!$mform->elementExists('loadimportassign')) {
            return;
        }

        if (optional_param('loadimportassign', '', PARAM_RAW) === '') {
            return;
        }

        $assigncmid = optional_param('importassigncmid', 0, PARAM_INT);
        $validoptions = aiproofreader_get_course_assign_options($COURSE->id, $this->get_import_section_filter());

        if ($assigncmid > 0 && array_key_exists($assigncmid, $validoptions)) {
            $importdata = \mod_aiproofreader\assign_importer::build_import_data($assigncmid);
            $importdata->sourceassigncmid = $assigncmid;
            $mform->setConstants((array) $importdata);
        }
    }

    /**
     * The course section number to restrict the Assignment import dropdown
     * to, based on the "Only show items in this section" checkbox - or null
     * for no restriction (checkbox unchecked, or no target section known).
     *
     * @return int|null
     */
    protected function get_import_section_filter() {
        $currentsectiononly = (bool) optional_param('importcurrentsectiononly', 1, PARAM_BOOL);
        if (!$currentsectiononly) {
            return null;
        }
        return isset($this->current->section) ? (int) $this->current->section : null;
    }

    /**
     * Adds the custom "must make a final submission" completion rule checkbox.
     *
     * @return array
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $suffix = $this->get_suffix();
        $completionsubmitel = 'completionsubmit' . $suffix;
        $mform->addElement('advcheckbox', $completionsubmitel, '', get_string('completionsubmit', 'aiproofreader'));
        $mform->setDefault($completionsubmitel, 0);
        return [$completionsubmitel];
    }

    /**
     * Checks whether the completion rule checkbox was ticked.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['completionsubmit' . $suffix]);
    }

    /**
     * Preloads editor and file manager content when editing an existing instance.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        if (isset($defaultvalues['aiinstructions'])) {
            $defaultvalues['aiinstructions_editor']['text'] = $defaultvalues['aiinstructions'];
            $defaultvalues['aiinstructions_editor']['format'] = isset($defaultvalues['aiinstructionsformat'])
                ? $defaultvalues['aiinstructionsformat']
                : FORMAT_HTML;
        } else {
            $defaultvalues['aiinstructions_editor']['text'] = '';
            $defaultvalues['aiinstructions_editor']['format'] = FORMAT_HTML;
        }

        $draftitemid = file_get_submitted_draft_itemid('additionalfiles');
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            'mod_aiproofreader',
            'additionalfiles',
            0,
            aiproofreader_get_additionalfiles_options()
        );
        $defaultvalues['additionalfiles_filemanager'] = $draftitemid;
    }

    /**
     * Validates the submitted activity settings.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['submtext']) && empty($data['submfile']) && empty($data['submgdrive'])) {
            $errors['submtext'] = get_string('err_nosubmissiontype', 'aiproofreader');
        }

        if (
            !empty($data['duedate']) && !empty($data['allowsubmissionsfromdate'])
                && $data['duedate'] < $data['allowsubmissionsfromdate']
        ) {
            $errors['duedate'] = get_string('err_duedatebeforeallow', 'aiproofreader');
        }

        if (!empty($data['cutoffdate']) && !empty($data['duedate']) && $data['cutoffdate'] < $data['duedate']) {
            $errors['cutoffdate'] = get_string('err_cutoffdatebeforedue', 'aiproofreader');
        }

        if ($data['grade'] === '' || (int)$data['grade'] <= 0) {
            $errors['grade'] = get_string('err_gradepositive', 'aiproofreader');
        }

        if ($data['gradepass'] !== '' && !is_numeric($data['gradepass'])) {
            $errors['gradepass'] = get_string('err_gradepassnumeric', 'aiproofreader');
        } else if ($data['gradepass'] !== '' && (float)$data['gradepass'] > (float)$data['grade']) {
            $errors['gradepass'] = get_string('err_gradepassexceedsmax', 'aiproofreader');
        }

        return $errors;
    }
}
