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
 * External services for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_aiproofreader_generate_feedback' => [
        'classname'     => 'mod_aiproofreader\external\generate_feedback',
        'methodname'    => 'execute',
        'description'   => 'Generate AI feedback for a submitted draft',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'mod_aiproofreader_generate_comparison' => [
        'classname'     => 'mod_aiproofreader\external\generate_comparison',
        'methodname'    => 'execute',
        'description'   => 'Generate the AI draft-vs-final comparison for grading',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
