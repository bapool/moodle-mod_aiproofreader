# AI Proofreader

An activity module for Moodle 4.5 that gives students AI-generated writing feedback on a draft before they submit a final version, then helps the teacher grade with the full picture: draft, feedback, final, and an AI comparison of whether the feedback was actually followed.

Built for National Trail Local Schools as part of the K-12 AI Infrastructure Program grant, focused on formative writing feedback in ELA, Science, and Social Studies (grades 5-12, though it works for any grade level from 3-12).

## How it works

1. The teacher creates an AI Proofreader activity with assignment instructions, a grade level, and optional additional AI instructions (a private field only the AI sees - useful for a rubric's key points or specific concepts a strong answer should cover).
2. The student submits a draft (typed text, an uploaded Word document, a pasted Google Drive link, or a Google Doc selected directly from their Drive via the file picker).
3. The AI generates feedback split into two parts: **Grammar and Spelling**, and **Assignment Specifics** - calibrated to the student's grade level and target reading level (Lexile).
4. If surveys are turned on (see below), the student completes a short survey, then submits a final version. If surveys are off, the student just submits a final version.
5. The AI compares the draft, the feedback, and the final version, and generates a 1-5 score for the teacher on how well the student incorporated the feedback - weighted so it doesn't penalize students for skipping feedback that was optional depth versus something that was actually required.
6. The teacher reviews everything (draft, feedback, final, AI comparison, and the student's survey answers if surveys are on), completes a short survey of their own if surveys are on, and enters a grade.
7. On the grading page the draft and final are shown with the student's own formatting (bold, italics, indents, alignment, line spacing - e.g. MLA format), whether they were typed, uploaded as Word documents, or submitted from Google Docs, with a link to the original file or Doc underneath. The page also says whether the final version actually differs from the draft - the only change check available for Google Drive and uploaded submissions, whose content can't be compared before the student submits (students choosing those types are told this on the final submission form).
8. If a final version is unacceptable, or was submitted for grading by mistake, the teacher can click "Return to draft" (on the overview table or the grading page) to send it back for revision. The student is notified via Moodle's messaging system (bell icon plus email, per their own notification preferences), sees their existing AI feedback again, and resubmits a final version, with their previous survey answers pre-filled (and editable - changing them updates their existing answers rather than adding a second set). Returning an already-graded submission clears the grade and removes it from the gradebook until it's regraded.

## Requirements

- Moodle 4.5 or later
- Moodle's core AI subsystem configured with a working AI provider (the plugin calls `core_ai\aiactions\generate_text`)
- Optional: an OpenAI-compatible chat completions endpoint serving a vision-language model (for example vLLM), if you want images in the assignment instructions sent to the AI - see "Image input" below. This is the only case where the plugin talks to an AI service directly.

## Installation

1. Copy the `aiproofreader` folder into `mod/` in your Moodle codebase.
2. Visit **Site Administration -> Notifications** to complete the install.
3. Optionally review **Site Administration -> Plugins -> Activity modules -> AI Proofreader** to adjust the default AI instructions (the base proofreading philosophy and response format sent to the AI for every instance on the site).
   - Under **Default grade level**, "Student grade level source" defaults to None (new activities start at grade 9). Choose the username or profile-field option if your site can supply each student's grade. This also applies when upgrading from an earlier version.
   - Under **Image input (vision model)**, leave "Send images to the AI" off unless you have an OpenAI-compatible endpoint serving a vision-language model.
4. Install **local_aiproofreaderreport** (a separate, required companion plugin - see its own README) to turn on surveys, customize survey questions, control Google Doc text retention, and see usage/survey reporting. AI Proofreader will not install without it already present, or upgrade past it.

## Settings overview

Each activity instance has its own:
- **Assignment instructions** - shown to students, and used as the primary instructions sent to the AI
- **Additional AI instructions** - never shown to students; use this for rubric basics or specific concepts a strong response should cover
- **Additional files** - optional attachments (e.g. a lab sheet) shown to students alongside the instructions
- **Grade level** - a combined grade + target Lexile dropdown (3-12), used to calibrate the AI's vocabulary and complexity. For a new activity it defaults to the lowest grade among the students enrolled in the course (see "Default grade level" below)
- **Minimum length** - 3 sentences (the default) or 2-10 paragraphs. Typed drafts (and typed final versions) can't be submitted below it; Google Drive and uploaded drafts are accepted, but the student's AI feedback says the draft is too short, and the grading page flags a final version that is too short. A paragraph counts when it has at least two sentences, so MLA heading lines and a title aren't counted, and counting stops at a "Works Cited", "Bibliography" or "References" heading
- **Submission types** - online text, Word file upload, and/or Google Drive (a pasted link, or a Google Doc picked directly from Drive via the file picker - either satisfies this type) (at least one required)
- **Grade type** - Point (a grade out of Maximum points, sent to the gradebook) or None (not graded: the teacher still reviews the final version, adds comments and completes the survey, and students see "Teacher review" instead of a grade)
- Standard availability, grade (points, category, pass grade), and completion settings, including the "View the activity" condition. The "Student must make a final submission to complete this activity" rule appears under Completion conditions, and only in courses with completion tracking turned on

When adding a brand-new activity, teachers can also optionally **import from an existing Assignment** in the same course - a dropdown and "Load" button prefill the name, description, dates, grade (including grade type None), grade category, completion conditions (view, submission, receive a grade, passing grade), Restrict access conditions (shown in the form so they can be changed before saving) and the Assignment's own attached files (copied into Additional files, alongside any the teacher already added) from it. The dropdown defaults to showing only Assignments in the section the new activity is being added to (a checkbox lets a teacher broaden it to the whole course - after changing it, click "Load" once to refresh the list, even with nothing selected yet). Saving then automatically positions the new activity directly after the source Assignment and hides the source Assignment from students (it isn't deleted).

Site-wide, controlled from this plugin's own settings (Site administration -> Plugins -> Activity modules -> AI Proofreader):
- **Default grade level** - where each student's grade level comes from, used to pre-fill a new activity's Grade level: the two-digit graduation year at the start of the username (e.g. `27jsmith`; the school year rolls over on 1 July), a custom user profile field, or none (always grade 9 - this is the default). Grades below 3 are rounded up to 3.
- **Image input (vision model)** - off by default. Moodle 4.5's AI subsystem can only send text. When this is on and an activity's instructions or Additional files contain images (PNG, JPEG, GIF or WebP), the prompt and the images (resized to at most 1568 pixels on the longest side, up to a set number per request) are sent directly to the configured OpenAI-compatible chat completions URL, model name and optional API key. Activities without images, and any image request that fails or times out, go through Moodle's AI subsystem as usual. Direct requests are not recorded in Moodle's AI usage reports, and the endpoint's host must not be on Moodle's cURL blocked hosts list.
- **PII redaction** - off by default. When enabled, a nightly scheduled task creates a de-identified copy of each not-yet-processed draft/final submission, for safer use when releasing student writing publicly (see Known limitations for details). A batch size setting bounds how many submissions of each type it will process per run.

Site-wide, controlled from **local_aiproofreaderreport**'s settings (not from this plugin):
- **Survey on/off** - off by default. While off, no survey questions are shown to anyone, and this plugin runs in feedback-only mode.
- **Per-question show/hide and custom wording** - for each of the 5 student and 6 teacher survey questions (plus each side's free-text box).
- **Google Doc text retention** - off by default. The text of a submitted Google Doc is always fetched briefly so the AI can process it, then cleared back to just the link afterward unless this is turned on.

## Accessibility

The activity description and each AI feedback section (Grammar and Spelling, Assignment Specifics, AI notes on your revision) include a "Read aloud" button that uses the student's own browser's built-in text-to-speech engine (the Web Speech API) - no server-side audio generation, external AI provider, or file storage involved. Voice quality depends on the browser/OS; the buttons hide themselves automatically on browsers with no speech synthesis support.

## Known limitations

- No automated PHPUnit or Behat tests.
- The "Hide grader identity from students" setting is stored but not yet enforced anywhere in the UI, since nothing currently displays grader identity to students in the first place.
- Google Drive submissions made by pasting a link require the document to be shared as "Anyone with the link can view" (or comment/edit) - the plugin cannot read privately-shared docs and will reject the submission at the form-validation stage if it can't read the content. This sharing requirement does not apply when the student instead selects the doc via the Google Drive file picker, since that downloads a copy through the student's own authenticated Drive connection rather than fetching a public export link.
- Google Docs selected via the file picker are downloaded as `.docx` at submission time (governed by the site's Google Drive repository configuration, under Site Administration -> Plugins -> Repositories); no live link back to the original Doc is stored, so `initialgdrivelink`/`finalgdrivelink` stay empty for submissions made this way.
- A draft that's abandoned before final submission (student never finishes) can leave fetched Google Doc text sitting in `initialtext` even with text retention off, since that text is only purged once the final-submission AI comparison step runs. The weekly cleanup task in local_aiproofreaderreport will eventually remove the whole submission row if the activity or student account is later deleted, but does not otherwise sweep abandoned drafts on a timer.
- The minimum length is only enforced at submission time for the online text type (a simple terminal-punctuation heuristic on the plain-text-converted content). File uploads and Google Drive submissions are accepted and flagged instead (in the student's feedback for drafts, on the grading page for final versions).
- The "no changes made" check that blocks submission only covers the online text type. For Google Drive and uploaded final versions the change check runs after submission and is shown to the teacher.
- Formatted (HTML) views are only produced for typed text and `.docx` files (including Google Docs, fetched as `.docx`). Legacy `.doc` uploads show the extracted text plus a download link. Images, headers/footers, footnotes and comments in Word documents are not shown in the formatted view, and list bullets/numbers are not reproduced.
- Assignment Import doesn't carry over the grade if the source Assignment uses a grading scale instead of points - AI Proofreader only supports point grading, so the maximum grade is left at its default and needs setting manually in that case.
- The nightly PII redaction task's output is AI-generated and not guaranteed to be complete or accurate - it can miss a name, or occasionally over-redact something that isn't actually personal information (e.g. an unusual word it mistakes for a name). It is not a substitute for a human review pass before any writing is actually published; the stored per-submission placeholder count is meant as a quick spot-check aid, not a guarantee.

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
| `minlength` | int(2) | Minimum draft length: 0 = 3 sentences (default), 2-10 = that many paragraphs |
| `aiinstructions` | text | Additional AI-only instructions from the teacher - never shown to students |
| `aiinstructionsformat` | int | Format of `aiinstructions` |
| `submtext` | int(1) | 1 if online text submission is enabled |
| `submfile` | int(1) | 1 if Word file submission is enabled |
| `submgdrive` | int(1) | 1 if Google Drive link submission is enabled |
| `grade` | int | Maximum points for this activity; 0 means grade type "None" (not graded) |
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
| `initialtext` | text | Draft text - typed directly, extracted from an uploaded Word file, fetched from a pasted Google Drive link, or extracted from a Google Doc selected via the file picker |
| `initialtexthtml` | text | Draft with the student's formatting, as HTML: the editor HTML for typed text, or converted from the Word file / Google Doc (fetched as `.docx`). Null for legacy `.doc` uploads. Cleared along with `initialtext` when Google Doc text retention is off |
| `initialtextredacted` | text | AI-redacted, de-identified copy of `initialtext`, populated by the nightly PII redaction task (off by default) - never overwrites the original |
| `initialtextredactedat` | int(10) | When `initialtextredacted` was generated; null means not yet processed |
| `initialtextpiicount` | int(10) | Number of PII placeholders inserted into `initialtextredacted` |
| `initialgdrivelink` | char(255) | Draft Google Drive link, only populated when that submission was made by pasting a link (empty when made via the Google Drive file picker instead) |
| `initialtimesubmitted` | int | Unix timestamp |
| `feedbackgrammar` | text | AI feedback: Grammar and Spelling |
| `feedbackassignment` | text | AI feedback: Assignment Specifics |
| `feedbacktimecreated` | int | Unix timestamp |
| `feedbackaimodel` | char(255) | Label identifying the AI model/provider that generated the feedback - see "AI model tracking" below |
| `finalsubmissiontype` | char(10) | `text`, `file`, or `gdrive` |
| `finaltext` | text | Final text, same sourcing as `initialtext` |
| `finaltexthtml` | text | Final version with the student's formatting, as HTML - see `initialtexthtml` above |
| `finaltextredacted` | text | AI-redacted, de-identified copy of `finaltext` - see `initialtextredacted` above |
| `finaltextredactedat` | int(10) | When `finaltextredacted` was generated; null means not yet processed |
| `finaltextpiicount` | int(10) | Number of PII placeholders inserted into `finaltextredacted` |
| `finalgdrivelink` | char(255) | Final Google Drive link, only populated when that submission was made by pasting a link (empty when made via the Google Drive file picker instead) |
| `finaltimesubmitted` | int | Unix timestamp |
| `aicomparison` | text | AI's narrative comparison of draft vs. feedback vs. final, shown to both student and teacher |
| `aicomparisontimecreated` | int | Unix timestamp |
| `comparisonaimodel` | char(255) | Label identifying the AI model/provider that generated the comparison - see "AI model tracking" below |
| `aifollowedscore` | int(2) | AI-generated 1-5 score of how well the student incorporated the feedback - teacher-only |
| `finalchanged` | int(1) | Whether the final text differs from the draft (ignoring whitespace and capitalization): 1 = changed, 0 = identical, null = could not be checked. Set at final submission and also passed to the AI comparison |
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
| `grade` | int | Points awarded (0 for activities with grade type "None") |
| `instructorcomments` | text | Instructor comments |
| `instructorcommentsformat` | int | Format of `instructorcomments` |
| `timemodified` | int | Unix timestamp |
