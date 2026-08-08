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
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Restore activity task for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aiproofreader/backup/moodle2/restore_aiproofreader_stepslib.php');
require_once($CFG->dirroot . '/mod/aiproofreader/backup/moodle2/restore_aiproofreader_settingslib.php');

/**
 * Restore task for the aiproofreader activity module.
 *
 * Provides all the settings and steps to perform a complete restore of the activity.
 */
class restore_aiproofreader_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_aiproofreader_activity_structure_step(
            'aiproofreader_structure',
            'aiproofreader.xml'
        ));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array of restore_decode_content
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('aiproofreader', ['intro'], 'aiproofreader');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity, executed by the link decoder.
     *
     * @return array of restore_decode_rule
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('AIPROOFREADERVIEWBYID', '/mod/aiproofreader/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('AIPROOFREADERINDEX', '/mod/aiproofreader/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules applied when restoring aiproofreader logs.
     *
     * @return array of restore_log_rule
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('aiproofreader', 'add', 'view.php?id={course_module}', '{aiproofreader}');
        $rules[] = new restore_log_rule('aiproofreader', 'update', 'view.php?id={course_module}', '{aiproofreader}');
        $rules[] = new restore_log_rule('aiproofreader', 'view', 'view.php?id={course_module}', '{aiproofreader}');

        return $rules;
    }

    /**
     * Define the restore log rules applied when restoring course logs, at activity level.
     * These rules are not linked to any module instance (cmid = 0).
     *
     * @return array of restore_log_rule
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('aiproofreader', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
