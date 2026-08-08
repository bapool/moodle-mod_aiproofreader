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
 * External service that generates the AI draft-vs-final comparison for the
 * grader, called asynchronously so the final submission save never blocks.
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
 * External function that generates the AI draft-vs-final comparison.
 */
class generate_comparison extends external_api {
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
     * Generates the AI comparison for the given submission.
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

            // Either the owning student's own page (harmless to re-trigger) or a grader may call this.
            $isowner = ($submission->userid == $USER->id);
            $isgrader = has_capability('mod/aiproofreader:viewallsubmissions', $context);
            if (!$isowner && !$isgrader) {
                throw new \moodle_exception('nopermissions', 'error', '', 'generate comparison for this submission');
            }

            if (
                empty($submission->aicomparison) && $submission->status !== 'draft'
                    && $submission->status !== 'feedbackpending'
            ) {
                \mod_aiproofreader\submission_manager::generate_comparison($aiproofreader, $submission);
            }

            $updated = $DB->get_record('aiproofreader_submission', ['id' => $params['submissionid']], '*', MUST_EXIST);

            return [
                'success' => !empty($updated->aicomparison),
                'error' => '',
            ];
        } catch (\Throwable $e) {
            debugging('mod_aiproofreader: generate_comparison external call failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
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
            'success' => new external_value(PARAM_BOOL, 'Whether a comparison is now available'),
            'error' => new external_value(PARAM_TEXT, 'Error message if any'),
        ]);
    }
}
