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
 * The draft submission form.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Draft submission form. Only shows the submission types enabled on the instance.
 */
class draft_form extends \moodleform {
    /**
     * Defines the draft submission form fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $aiproofreader = $this->_customdata['aiproofreader'];

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
            $editoroptions = [
                'maxfiles' => 0,
                'noclean' => false,
                'subdirs' => false,
                'trusttext' => false,
                'context' => $this->_customdata['context'],
            ];
            $mform->addElement(
                'editor',
                'onlinetext_editor',
                get_string('onlinetextlabel', 'aiproofreader'),
                null,
                $editoroptions
            );
            $mform->setType('onlinetext_editor', PARAM_RAW);
            if ($multipletypes) {
                $mform->hideIf('onlinetext_editor', 'submissiontype', 'neq', 'text');
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

            $mform->addElement(
                'filepicker',
                'gdrivefile',
                get_string('gdrivefilelabel', 'aiproofreader'),
                null,
                ['maxbytes' => 10485760, 'accepted_types' => ['.docx']]
            );
            if ($multipletypes) {
                $mform->hideIf('gdrivefile', 'submissiontype', 'neq', 'gdrive');
            }
        }

        $this->add_action_buttons(false, get_string('submitdraft', 'aiproofreader'));
    }

    /**
     * Validates the submitted draft.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['submissiontype'] === 'text') {
            $plaintext = aiproofreader_editor_html_to_text($data['onlinetext_editor']['text'] ?? '');
            if ($plaintext === '') {
                $errors['onlinetext_editor'] = get_string('err_notextentered', 'aiproofreader');
            } else if (aiproofreader_count_sentences($plaintext) < 3) {
                $errors['onlinetext_editor'] = get_string('err_mintextlength', 'aiproofreader');
            }
        }

        if ($data['submissiontype'] === 'gdrive') {
            $gdrivefileitemid = file_get_submitted_draft_itemid('gdrivefile');
            $gdrivefiles = file_get_drafarea_files($gdrivefileitemid);
            $filepicked = !empty($gdrivefiles) && !empty($gdrivefiles->list);

            if (!$filepicked) {
                if (empty($data['gdrivelink']) || !filter_var($data['gdrivelink'], FILTER_VALIDATE_URL)) {
                    $errors['gdrivelink'] = get_string('err_invalidgdrivelink', 'aiproofreader');
                } else if (empty(\mod_aiproofreader\submission_manager::fetch_gdrive_text($data['gdrivelink']))) {
                    $errors['gdrivelink'] = get_string('gdrivefetchfailed', 'aiproofreader');
                }
            }
        }

        if ($data['submissiontype'] === 'file') {
            $draftitemid = file_get_submitted_draft_itemid('submissionfile');
            $files = file_get_drafarea_files($draftitemid);
            if (empty($files) || empty($files->list)) {
                $errors['submissionfile'] = get_string('err_nofileuploaded', 'aiproofreader');
            }
        }

        return $errors;
    }
}
