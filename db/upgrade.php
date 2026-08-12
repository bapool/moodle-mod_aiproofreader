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

    if ($oldversion < 2026081000) {
        // Survey questions can now be individually enabled/disabled from
        // local_aiproofreaderreport, and the whole survey system can be
        // turned off. A disabled question is no longer collected, so these
        // columns can no longer be strictly required.
        $table = new xmldb_table('aiproofreader_studentsurvey');

        $field = new xmldb_field('q1overallfeedback', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'submissionid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field(
            'q2specificfeedback',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            false,
            null,
            null,
            'q1overallfeedback'
        );
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q3usedfeedback', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'q2specificfeedback');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q4categoryhelped', XMLDB_TYPE_CHAR, '10', null, false, null, null, 'q3usedfeedback');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q5confidence', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'q4categoryhelped');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        $table = new xmldb_table('aiproofreader_teachersurvey');

        $field = new xmldb_field('q1overallfeedback', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'graderid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field(
            'q2specificfeedback',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            false,
            null,
            null,
            'q1overallfeedback'
        );
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q3usedfeedback', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'q2specificfeedback');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field(
            'q4feedbackfollowed',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            false,
            null,
            null,
            'q3usedfeedback'
        );
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q5aiscaffold', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'q4feedbackfollowed');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        $field = new xmldb_field('q6aiaccuracy', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'q5aiscaffold');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }

        upgrade_mod_savepoint(true, 2026081000, 'aiproofreader');
    }

    if ($oldversion < 2026081100) {
        // Track which AI model/provider generated each submission's feedback
        // and comparison, so quarterly model changes can be compared against
        // student/teacher survey results.
        $table = new xmldb_table('aiproofreader_submission');

        $field = new xmldb_field(
            'feedbackaimodel',
            XMLDB_TYPE_CHAR,
            '255',
            null,
            false,
            null,
            null,
            'feedbacktimecreated'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'comparisonaimodel',
            XMLDB_TYPE_CHAR,
            '255',
            null,
            false,
            null,
            null,
            'aicomparisontimecreated'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026081100, 'aiproofreader');
    }

    return true;
}
