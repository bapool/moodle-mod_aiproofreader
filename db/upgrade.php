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
 * Upgrade steps for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Executes the upgrade steps for mod_aiproofreader.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_aiproofreader_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026080701) {
        // Grade level and lexile were merged into a single settings field;
        // the lexile value is now looked up from gradelevel in lib.php instead.
        $table = new xmldb_table('aiproofreader');
        $field = new xmldb_field('lexilelevel', XMLDB_TYPE_CHAR, '10', null, false, null, null, 'gradelevel');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026080701, 'aiproofreader');
    }

    if ($oldversion < 2026080702) {
        // New setting: hide grader identity from students.
        $table = new xmldb_table('aiproofreader');
        $field = new xmldb_field('hidegrader', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'grade');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026080702, 'aiproofreader');
    }

    if ($oldversion < 2026080704) {
        // New: AI-generated 1-5 score of how well the student followed the
        // feedback, shown to the teacher before grading.
        $table = new xmldb_table('aiproofreader_submission');
        $field = new xmldb_field(
            'aifollowedscore',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            false,
            null,
            null,
            'aicomparisontimecreated'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026080704, 'aiproofreader');
    }

    if ($oldversion < 2026080705) {
        // New: teacher-only "Learning objectives" checklist, used to inform
        // the AI's Assignment Specifics evaluation. Never shown to students.
        $table = new xmldb_table('aiproofreader');

        $field = new xmldb_field('learningobjectives', XMLDB_TYPE_TEXT, null, null, false, null, null, 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'learningobjectivesformat',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'learningobjectives'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026080705, 'aiproofreader');
    }

    if ($oldversion < 2026080706) {
        // Learning objectives turned out to be functionally redundant with
        // the existing Additional AI instructions field - both are teacher-
        // only free text combined into the same prompt with no meaningful
        // difference in how the AI treats them. Consolidated back into one
        // field rather than keeping two that do the same job.
        $table = new xmldb_table('aiproofreader');

        $field = new xmldb_field(
            'learningobjectivesformat',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'learningobjectives'
        );
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('learningobjectives', XMLDB_TYPE_TEXT, null, null, false, null, null, 'introformat');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026080706, 'aiproofreader');
    }

    return true;
}
