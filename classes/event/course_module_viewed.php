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
 * The mod_aiproofreader course module viewed event.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader\event;


/**
 * The mod_aiproofreader course module viewed event class.
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'aiproofreader';
        parent::init();
    }

    /**
     * Returns the object id mapping used when restoring this event from a backup.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'aiproofreader', 'restore' => 'aiproofreader'];
    }
}
