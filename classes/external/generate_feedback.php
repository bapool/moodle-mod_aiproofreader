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
 * External service that generates AI feedback for a submitted draft, called
 * asynchronously via AJAX so the draft save itself never blocks on the AI server.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

/**
 * External function that generates AI feedback for a submitted draft.
 */
class generate_feedback extends external_api {
    /**
     * Describes the parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT, 'aiproofreader_submission id'),
        ]);
    }

    /**
     * Generates AI feedback for the given submission's draft.
     *
     * @param int $submissionid
     * @return array
     */
    public static function execute($submissionid) {
        global $DB, $USER;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'submissionid' => $submissionid,
            ]);

            $submission = $DB->get_record('aiproofreader_submission', ['id' => $params['submissionid']], '*', MUST_EXIST);
            $aiproofreader = $DB->get_record('aiproofreader', ['id' => $submission->aiproofreaderid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance(
                'aiproofreader',
                $aiproofreader->id,
                $aiproofreader->course,
                false,
                MUST_EXIST
            );
            $context = context_module::instance($cm->id);

            self::validate_context($context);
            require_capability('mod/aiproofreader:submit', $context);

            if ($submission->userid != $USER->id) {
                throw new \moodle_exception('nopermissions', 'error', '', 'generate feedback for this submission');
            }

            if ($submission->status !== 'feedbackpending') {
                // Already processed (or in a later state) - just report the current status.
                return [
                    'success' => ($submission->status === 'feedbackready'),
                    'status' => $submission->status,
                    'error' => '',
                ];
            }

            $errormessage = \mod_aiproofreader\submission_manager::generate_feedback($aiproofreader, $submission);

            $updated = $DB->get_record('aiproofreader_submission', ['id' => $params['submissionid']], '*', MUST_EXIST);

            return [
                'success' => ($updated->status === 'feedbackready'),
                'status' => $updated->status,
                'error' => $errormessage,
            ];
        } catch (\Throwable $e) {
            // Never let a raw exception reach the browser as a disruptive popup -
            // report it as a graceful failure so the normal retry UI handles it.
            debugging('mod_aiproofreader: generate_feedback external call failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'status' => 'feedbackpending',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Describes the return structure of execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether feedback was generated successfully'),
            'status' => new external_value(PARAM_ALPHA, 'The submission status after this call'),
            'error' => new external_value(PARAM_TEXT, 'Error message if any'),
        ]);
    }
}
