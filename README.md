# AI Proofreader

An activity module for Moodle 4.5 that gives students AI-generated writing feedback on a draft before they submit a final version, then helps the teacher grade with the full picture: draft, feedback, final, and an AI comparison of whether the feedback was actually followed.

Built for National Trail Local Schools as part of the K-12 AI Infrastructure Program grant, focused on formative writing feedback in ELA, Science, and Social Studies (grades 5-12, though it works for any grade level from 3-12).

## How it works

1. The teacher creates an AI Proofreader activity with assignment instructions, a grade level, and optional additional AI instructions (a private field only the AI sees - useful for a rubric's key points or specific concepts a strong answer should cover).
2. The student submits a draft (typed text, an uploaded Word document, or a Google Drive link).
3. The AI generates feedback split into two parts: **Grammar and Spelling**, and **Assignment Specifics** - calibrated to the student's grade level and target reading level (Lexile).
4. If surveys are turned on (see below), the student completes a short survey, then submits a final version. If surveys are off, the student just submits a final version.
5. The AI compares the draft, the feedback, and the final version, and generates a 1-5 score for the teacher on how well the student incorporated the feedback - weighted so it doesn't penalize students for skipping feedback that was optional depth versus something that was actually required.
6. The teacher reviews everything (draft, feedback, final, AI comparison, and the student's survey answers if surveys are on), completes a short survey of their own if surveys are on, and enters a grade.

## Requirements

- Moodle 4.5 or later
- Moodle's core AI subsystem configured with a working AI provider (the plugin calls `core_ai\aiactions\generate_text` - it does not talk to any AI service directly)

## Installation

1. Copy the `aiproofreader` folder into `mod/` in your Moodle codebase.
2. Visit **Site Administration -> Notifications** to complete the install.
3. Optionally review **Site Administration -> Plugins -> Activity modules -> AI Proofreader** to adjust the default AI instructions (the base proofreading philosophy and response format sent to the AI for every instance on the site).
4. Install **local_aiproofreaderreport** (a separate, required companion plugin - see its own README) to turn on surveys, customize survey questions, control Google Doc text retention, and see usage/survey reporting. AI Proofreader will not install without it already present, or upgrade past it.

## Settings overview

Each activity instance has its own:
- **Assignment instructions** - shown to students, and used as the primary instructions sent to the AI
- **Additional AI instructions** - never shown to students; use this for rubric basics or specific concepts a strong response should cover
- **Additional files** - optional attachments (e.g. a lab sheet) shown to students alongside the instructions
- **Grade level** - a combined grade + target Lexile dropdown (3-12), used to calibrate the AI's vocabulary and complexity
- **Submission types** - online text, Word file upload, and/or a Google Drive link (at least one required)
- Standard availability, grade (points, category, pass grade), and completion settings

When adding a brand-new activity, teachers can also optionally **import from an existing Assignment** in the same course - a dropdown and "Load" button prefill the name, description, dates, grade, grade category, and completion settings from it. Saving then automatically positions the new activity directly after the source Assignment, copies its Restrict Access conditions, and hides the source Assignment from students (it isn't deleted).

Site-wide, controlled from **local_aiproofreaderreport**'s settings (not from this plugin):
- **Survey on/off** - off by default. While off, no survey questions are shown to anyone, and this plugin runs in feedback-only mode.
- **Per-question show/hide and custom wording** - for each of the 5 student and 6 teacher survey questions (plus each side's free-text box).
- **Google Doc text retention** - off by default. The text of a submitted Google Doc is always fetched briefly so the AI can process it, then cleared back to just the link afterward unless this is turned on.

## Accessibility

The activity description and each AI feedback section (Grammar and Spelling, Assignment Specifics, AI notes on your revision) include a "Read aloud" button that uses the student's own browser's built-in text-to-speech engine (the Web Speech API) - no server-side audio generation, external AI provider, or file storage involved. Voice quality depends on the browser/OS; the buttons hide themselves automatically on browsers with no speech synthesis support.

## Known limitations

- No automated PHPUnit or Behat tests.
- The "Hide grader identity from students" setting is stored but not yet enforced anywhere in the UI, since nothing currently displays grader identity to students in the first place.
- Google Drive submissions require the document to be shared as "Anyone with the link can view" (or comment/edit) - the plugin cannot read privately-shared docs and will reject the submission at the form-validation stage if it can't read the content.
- A draft that's abandoned before final submission (student never finishes) can leave fetched Google Doc text sitting in `initialtext` even with text retention off, since that text is only purged once the final-submission AI comparison step runs. The weekly cleanup task in local_aiproofreaderreport will eventually remove the whole submission row if the activity or student account is later deleted, but does not otherwise sweep abandoned drafts on a timer.
- The 3-sentence minimum on draft submissions only applies to the online text type (a simple terminal-punctuation heuristic on the plain-text-converted content) - file uploads and Google Drive links aren't length-checked at submission time.
- Assignment Import doesn't carry over the grade if the source Assignment uses a grading scale instead of points - AI Proofreader only supports point grading, so the maximum grade is left at its default and needs setting manually in that case.

## License

GNU GPL v3 or later. See <http://www.gnu.org/copyleft/gpl.html>.

## Author

Brian Pool

---

## Database dictionary

### `aiproofreader`
One row per activity instance.

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key |
| `course` | int | Course ID (FK to `course`) |
| `name` | char(255) | Assignment name |
| `intro` | text | Assignment instructions - shown to students, sent to the AI as the primary instructions |
| `introformat` | int | Format of `intro` |
| `allowsubmissionsfromdate` | int | Unix timestamp, 0 = not set |
| `duedate` | int | Unix timestamp, 0 = not set |
| `cutoffdate` | int | Unix timestamp, 0 = not set |
| `gradelevel` | char(2) | Grade level 3-12; also used to look up the target Lexile band via a fixed table in `lib.php` |
| `aiinstructions` | text | Additional AI-only instructions from the teacher - never shown to students |
| `aiinstructionsformat` | int | Format of `aiinstructions` |
| `submtext` | int(1) | 1 if online text submission is enabled |
| `submfile` | int(1) | 1 if Word file submission is enabled |
| `submgdrive` | int(1) | 1 if Google Drive link submission is enabled |
| `grade` | int | Maximum points for this activity |
| `hidegrader` | int(1) | Hide grader identity from students (stored; not yet enforced anywhere) |
| `completionsubmit` | int(1) | 1 if completion requires a final submission |
| `timecreated` | int | Unix timestamp |
| `timemodified` | int | Unix timestamp |

### `aiproofreader_submission`
One row per student per activity instance (no multiple attempts). Unique on (`aiproofreaderid`, `userid`).

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key |
| `aiproofreaderid` | int | FK to `aiproofreader` |
| `userid` | int | FK to `user` - the student |
| `status` | char(20) | `draft`, `feedbackpending`, `feedbackready`, `finalsubmitted`, or `graded` |
| `initialsubmissiontype` | char(10) | `text`, `file`, or `gdrive` |
| `initialtext` | text | Draft text - typed directly, extracted from an uploaded Word file, or fetched from a Google Doc |
| `initialgdrivelink` | char(255) | Draft Google Drive link, if that type was used |
| `initialtimesubmitted` | int | Unix timestamp |
| `feedbackgrammar` | text | AI feedback: Grammar and Spelling |
| `feedbackassignment` | text | AI feedback: Assignment Specifics |
| `feedbacktimecreated` | int | Unix timestamp |
| `feedbackaimodel` | char(255) | Label identifying the AI model/provider that generated the feedback - see "AI model tracking" below |
| `finalsubmissiontype` | char(10) | `text`, `file`, or `gdrive` |
| `finaltext` | text | Final text, same sourcing as `initialtext` |
| `finalgdrivelink` | char(255) | Final Google Drive link, if that type was used |
| `finaltimesubmitted` | int | Unix timestamp |
| `aicomparison` | text | AI's narrative comparison of draft vs. feedback vs. final, shown to both student and teacher |
| `aicomparisontimecreated` | int | Unix timestamp |
| `comparisonaimodel` | char(255) | Label identifying the AI model/provider that generated the comparison - see "AI model tracking" below |
| `aifollowedscore` | int(2) | AI-generated 1-5 score of how well the student incorporated the feedback - teacher-only |
| `timecreated` | int | Unix timestamp |
| `timemodified` | int | Unix timestamp |

### `aiproofreader_studentsurvey`
One row per submission. Required before a final submission is accepted. Unique on `submissionid`.

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key |
| `submissionid` | int | FK to `aiproofreader_submission` |
| `q1overallfeedback` | int(2) | 1-5: was the overall feedback useful |
| `q2specificfeedback` | int(2) | 1-5: was the assignment-specific feedback useful |
| `q3usedfeedback` | int(2) | 1-5: did you use the feedback to improve your submission |
| `q4categoryhelped` | char(10) | `grammar`, `assignment`, or `both` - which feedback category helped more |
| `q5confidence` | int(2) | 1-5: confidence in the final version compared to the draft |
| `freetext` | text | Optional: what the AI feedback missed |
| `timecreated` | int | Unix timestamp |

### `aiproofreader_teachersurvey`
One row per submission. Required before a grade is accepted. Unique on `submissionid`.

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key |
| `submissionid` | int | FK to `aiproofreader_submission` |
| `graderid` | int | FK to `user` - the grading teacher |
| `q1overallfeedback` | int(2) | 1-5: was the overall feedback useful |
| `q2specificfeedback` | int(2) | 1-5: was the assignment-specific feedback useful |
| `q3usedfeedback` | int(2) | 1-5: did the student use the feedback |
| `q4feedbackfollowed` | int(2) | 1-5: was the feedback followed |
| `q5aiscaffold` | int(2) | 1-5: did the AI help scaffold the student |
| `q6aiaccuracy` | int(2) | 1-5: was the AI feedback accurate for this assignment |
| `freetext` | text | Optional: concerns about the AI feedback |
| `timecreated` | int | Unix timestamp |

### `aiproofreader_grade`
One row per submission - the teacher's grade and comments. Unique on `submissionid`.

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key |
| `submissionid` | int | FK to `aiproofreader_submission` |
| `graderid` | int | FK to `user` - the grading teacher |
| `grade` | int | Points awarded |
| `instructorcomments` | text | Instructor comments |
| `instructorcommentsformat` | int | Format of `instructorcomments` |
| `timemodified` | int | Unix timestamp |
