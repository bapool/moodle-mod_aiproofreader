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
 * Resets the site-wide "Default AI instructions" setting back to the
 * plugin's built-in default text.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/aiproofreader/admin_restore_defaults.php');

set_config('defaultaiinstructions', get_string('defaultaiinstructions_default', 'aiproofreader'), 'aiproofreader');

redirect(
    new moodle_url('/admin/settings.php', ['section' => 'modsettingaiproofreader']),
    get_string('settings_restoredefault', 'aiproofreader'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
