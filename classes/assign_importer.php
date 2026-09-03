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
 * Builds mod_aiproofreader mod_form prefill data from an existing
 * Assignment activity, for the "convert to AI Proofreader" import tool.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aiproofreader;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads an existing Assignment course module and produces a prefill data
 * object for the AI Proofreader settings form (add mode).
 */
class assign_importer {

    /**
     * Builds the import data for a given source Assignment course module.
     *
     * The returned object's public (non-underscore-prefixed) properties
     * match mod_aiproofreader_mod_form field names directly, so they can be
     * passed straight into set_data(). Properties prefixed with an
     * underscore are extra context needed after the new activity is
     * created (see the importer controller in Stage 3/4) - they are not
     * form fields and moodleform will ignore unknown keys safely.
     *
     * @param int $assigncmid Course module id of the source Assignment.
     * @return \stdClass
     */
    public static function build_import_data($assigncmid) {
        global $DB;

        $cm = get_coursemodule_from_id('assign', $assigncmid, 0, false, MUST_EXIST);
        $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
        $sourcecontext = \context_module::instance($cm->id);

        $data = new \stdClass();
        $data->name = $assign->name;

        // Intro text and any embedded files (e.g. images), copied into a
        // fresh draft area so the new form's editor shows them exactly as
        // they appear on the source Assignment.
        $draftitemid = 0;
        $introtext = file_prepare_draft_area(
            $draftitemid,
            $sourcecontext->id,
            'mod_assign',
            'intro',
            0,
            ['subdirs' => 0, 'maxfiles' => EDITOR_UNLIMITED_FILES],
            $assign->intro
        );
        $data->introeditor = [
            'text' => $introtext,
            'format' => $assign->introformat,
            'itemid' => $draftitemid,
        ];

        // Dates: same field names and meaning (unix timestamps) on both
        // activity types, so these copy over directly.
        $data->allowsubmissionsfromdate = $assign->allowsubmissionsfromdate;
        $data->duedate = $assign->duedate;
        $data->cutoffdate = $assign->cutoffdate;

        // Grade: a negative value on mdl_assign.grade means the source uses
        // a grading scale rather than points. AI Proofreader only supports
        // point grading, so flag that rather than importing a nonsense
        // negative max grade - the caller should warn the teacher.
        $data->usesscale = ($assign->grade < 0);
        $data->grade = $data->usesscale ? 100 : $assign->grade;

        // Completion: carry over both the tracking type (the "None /
        // Manual / Add requirements" radio) and the actual checkbox value,
        // so the checkbox shows as active rather than just being set with
        // no visible effect.
        $data->completion = $cm->completion;
        $data->completionsubmit = ($cm->completion == COMPLETION_TRACKING_AUTOMATIC && !empty($assign->completionsubmit))
            ? 1
            : 0;

        // Grade category: not stored on the assign table itself - it lives
        // on the linked grade_items row.
        $gradeitem = $DB->get_record('grade_items', [
            'itemmodule' => 'assign',
            'iteminstance' => $assign->id,
            'itemtype' => 'mod',
            'courseid' => $cm->course,
        ]);
        if ($gradeitem && !empty($gradeitem->categoryid)) {
            $data->gradecat = $gradeitem->categoryid;
        }

        // Standard course-module settings that transfer directly.
        $data->visible = $cm->visible;
        $data->groupmode = $cm->groupmode;
        $data->groupingid = $cm->groupingid;

        // Extra context, not mod_form fields - used after the new course
        // module is created to finish positioning it, apply restrict-access
        // conditions, and hide the source Assignment.
        $data->_sourcecmid = $cm->id;
        $data->_sourcename = $assign->name;
        $data->_section = $cm->section;
        $data->_courseid = $cm->course;
        $data->_availability = $cm->availability;
        $data->_completiontracking = $cm->completion;
        $data->_completionexpected = $cm->completionexpected;

        return $data;
    }
}
