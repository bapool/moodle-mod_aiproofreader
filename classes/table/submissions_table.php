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

namespace mod_aiproofreader\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * The teacher's "Student submissions" table on view.php.
 *
 * Built on Moodle's flexible_table - the same table class the core
 * Assignment grading table uses - so it looks and sorts the way teachers
 * are used to: profile pictures, "First name / Last name" sort links, and
 * clickable column headings. Columns are name, action buttons, status (as a
 * coloured badge), grade (point-graded activities only), and when the
 * student last submitted. Grading itself stays on the grading pages; there
 * is deliberately no quick grading, so the teacher survey and AI comparison
 * review can't be skipped.
 *
 * Class sizes are small, so rows are built and sorted in PHP rather than
 * in SQL, and the table is not paged.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submissions_table extends \flexible_table {
    /** @var array Sort order for the Status column: work needing the teacher first. */
    protected const STATUS_RANK = [
        'submittedaftergrading' => 1,
        'finalsubmitted' => 2,
        'feedbackready' => 3,
        'feedbackpending' => 4,
        'draft' => 5,
        'gradedbeforesubmission' => 6,
        'graded' => 7,
    ];

    /** @var \stdClass The aiproofreader instance. */
    protected $aiproofreader;

    /** @var \stdClass|\cm_info The course module. */
    protected $cm;

    /** @var bool Whether the viewer can grade (and so sees the action buttons). */
    protected $cangrade;

    /** @var bool Whether the activity is graded with points. */
    protected $ispointgraded;

    /**
     * Sets up the columns, headings and sorting.
     *
     * @param \stdClass $aiproofreader
     * @param \stdClass|\cm_info $cm
     * @param bool $cangrade
     * @param \moodle_url $baseurl
     */
    public function __construct($aiproofreader, $cm, $cangrade, \moodle_url $baseurl) {
        parent::__construct('mod-aiproofreader-submissions-' . $cm->id);

        $this->aiproofreader = $aiproofreader;
        $this->cm = $cm;
        $this->cangrade = $cangrade;
        $this->ispointgraded = (int) $aiproofreader->grade > 0;

        $columns = ['fullname', 'actions', 'status'];
        $headers = [
            get_string('fullname'),
            get_string('actions'),
            get_string('yourstatus', 'aiproofreader'),
        ];
        if ($this->ispointgraded) {
            $columns[] = 'grade';
            $headers[] = get_string('grade', 'aiproofreader');
        }
        $columns[] = 'lastsubmitted';
        $headers[] = get_string('lastsubmitted', 'aiproofreader');

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);

        $this->sortable(true, 'lastname');
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->pageable(false);

        $this->set_attribute('class', 'generaltable aiproofreader-submissions');
        $this->column_class('fullname', 'text-nowrap');
        $this->column_class('actions', 'text-nowrap');
        $this->column_class('grade', 'text-nowrap');
        $this->column_class('lastsubmitted', 'text-nowrap');

        $this->setup();
    }

    /**
     * Builds, sorts and prints every row, then finishes the table.
     *
     * @param array $students Enrolled students, keyed by user id
     * @param array $byuserid Submission records, keyed by user id
     * @param array $grades Grade (points) for each graded submission, keyed by submission id
     */
    public function build(array $students, array $byuserid, array $grades) {
        global $OUTPUT;

        $gradenowavailable = aiproofreader_grade_now_available($this->aiproofreader);
        $rows = [];

        foreach ($students as $student) {
            $submission = $byuserid[$student->id] ?? null;
            $status = $submission ? $submission->status : 'draft';
            $grade = ($submission && array_key_exists($submission->id, $grades)) ? $grades[$submission->id] : null;
            $hasgrade = $grade !== null;

            // The status as the teacher sees it: a grade entered before the
            // final submission gets its own status.
            $displaystatus = $status;
            if ($hasgrade && in_array($status, ['draft', 'feedbackpending', 'feedbackready'])) {
                $displaystatus = 'gradedbeforesubmission';
            } else if ($hasgrade && $status === 'finalsubmitted') {
                $displaystatus = 'submittedaftergrading';
            }

            $lastsubmitted = 0;
            if ($submission) {
                $lastsubmitted = max((int) $submission->initialtimesubmitted, (int) ($submission->finaltimesubmitted ?? 0));
            }

            $rows[] = (object) [
                'student' => $student,
                'sortkeys' => [
                    'status' => self::STATUS_RANK[$displaystatus] ?? 99,
                    'grade' => $grade,
                    'lastsubmitted' => $lastsubmitted,
                ],
                'cells' => [
                    'fullname' => $OUTPUT->user_picture(
                        $student,
                        ['courseid' => $this->cm->course, 'includefullname' => true, 'size' => 35]
                    ),
                    'actions' => implode(' ', $this->action_buttons($student->id, $displaystatus, $gradenowavailable)),
                    'status' => $this->status_badge($displaystatus, $status),
                    'grade' => $hasgrade ? $grade . ' / ' . (int) $this->aiproofreader->grade : '-',
                    'lastsubmitted' => $lastsubmitted
                        ? userdate($lastsubmitted, get_string('strftimedatetimeshort', 'langconfig'))
                        : '-',
                ],
            ];
        }

        $this->sort_rows($rows);

        foreach ($rows as $row) {
            $this->add_data_keyed($row->cells);
        }

        $this->finish_output();
    }

    /**
     * The action buttons for one student. Primary (orange) buttons mark work
     * that needs grading; secondary (brown) buttons are everything else.
     *
     * @param int $userid
     * @param string $displaystatus
     * @param bool $gradenowavailable
     * @return string[] HTML buttons
     */
    protected function action_buttons($userid, $displaystatus, $gradenowavailable) {
        if (!$this->cangrade) {
            return [];
        }

        $cmid = $this->cm->id;
        switch ($displaystatus) {
            case 'gradedbeforesubmission':
                return $this->ispointgraded ? [aiproofreader_render_grade_now_link($cmid, $userid, true)] : [];
            case 'submittedaftergrading':
                return [
                    aiproofreader_render_grade_link($cmid, $userid, 'regrade', true),
                    aiproofreader_render_return_to_draft_link($cmid, $userid),
                ];
            case 'finalsubmitted':
                return [
                    aiproofreader_render_grade_link($cmid, $userid, 'grade', true),
                    aiproofreader_render_return_to_draft_link($cmid, $userid),
                ];
            case 'graded':
                return [
                    aiproofreader_render_grade_link($cmid, $userid, 'updategrade', false),
                    aiproofreader_render_return_to_draft_link($cmid, $userid),
                ];
            default:
                return $gradenowavailable ? [aiproofreader_render_grade_now_link($cmid, $userid, false)] : [];
        }
    }

    /**
     * The status as a coloured badge, like the core Assignment table: green
     * once graded, the theme's primary colour (orange) when the teacher needs
     * to grade, and light grey while the student hasn't started or is still
     * working.
     *
     * @param string $displaystatus
     * @param string $status The underlying workflow status
     * @return string HTML
     */
    protected function status_badge($displaystatus, $status) {
        switch ($displaystatus) {
            case 'graded':
            case 'gradedbeforesubmission':
                $style = 'badge-success';
                break;
            case 'finalsubmitted':
            case 'submittedaftergrading':
                $style = 'badge-primary';
                break;
            default:
                // Not started, or still working on the draft - nothing for the teacher to do yet.
                $style = 'badge-light';
        }

        $out = \html_writer::span(
            get_string('status' . $displaystatus, 'aiproofreader'),
            'badge ' . $style . ' aiproofreader-status'
        );

        // Where the student actually is, for a grade entered before they finished.
        if ($displaystatus === 'gradedbeforesubmission') {
            $out .= \html_writer::div(
                get_string('status' . $status, 'aiproofreader'),
                'text-muted small mt-1'
            );
        }

        return $out;
    }

    /**
     * Sorts the rows by the column(s) the teacher clicked. Name columns sort
     * alphabetically; Status sorts work needing the teacher first; rows with
     * no grade or no submission always sort last.
     *
     * @param array $rows
     */
    protected function sort_rows(array &$rows) {
        $sortcolumns = $this->get_sort_columns();
        if (empty($sortcolumns)) {
            $sortcolumns = ['lastname' => SORT_ASC];
        }
        // Always fall back to last name then first name, so ties stay in a stable, predictable order.
        $sortcolumns += ['lastname' => SORT_ASC, 'firstname' => SORT_ASC];

        usort($rows, function ($a, $b) use ($sortcolumns) {
            foreach ($sortcolumns as $column => $direction) {
                if (array_key_exists($column, $a->sortkeys)) {
                    $x = $a->sortkeys[$column];
                    $y = $b->sortkeys[$column];
                    // Empty values (no grade, never submitted) always go last.
                    $xempty = $x === null || ($column === 'lastsubmitted' && empty($x));
                    $yempty = $y === null || ($column === 'lastsubmitted' && empty($y));
                    if ($xempty !== $yempty) {
                        return $xempty ? 1 : -1;
                    }
                    $result = $x <=> $y;
                } else {
                    // A user name field (firstname, lastname, or an alternative name field).
                    $result = strcmp(
                        \core_text::strtolower((string) ($a->student->$column ?? '')),
                        \core_text::strtolower((string) ($b->student->$column ?? ''))
                    );
                }
                if ($result !== 0) {
                    return $direction == SORT_DESC ? -$result : $result;
                }
            }
            return 0;
        });
    }
}
