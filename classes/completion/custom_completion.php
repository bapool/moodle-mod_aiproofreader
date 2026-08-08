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
 * Custom completion rule for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_aiproofreader\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for AI Proofreader.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if ($rule === 'completionsubmit') {
            $submission = $DB->get_record('aiproofreader_submission', [
                'aiproofreaderid' => $this->cm->instance,
                'userid' => $this->userid,
            ]);

            if ($submission && in_array($submission->status, ['finalsubmitted', 'graded'])) {
                return COMPLETION_COMPLETE;
            }
            return COMPLETION_INCOMPLETE;
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Returns the list of custom completion rule identifiers this plugin defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit'];
    }

    /**
     * Returns the human-readable description for each custom completion rule.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionsubmit' => get_string('completionsubmit', 'mod_aiproofreader'),
        ];
    }

    /**
     * Returns the display order of the completion rules.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionsubmit',
        ];
    }
}
