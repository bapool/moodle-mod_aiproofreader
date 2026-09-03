# Changelog

All notable changes to AI Proofreader are documented here.

## v0.4.0 (2026090300)
### Added
- **Assignment Import**: when creating a brand-new AI Proofreader activity, teachers can now pick an existing Assignment activity in the same course from a dropdown and click "Load" to prefill the name, description (including embedded files), availability dates, maximum grade, grade category, and completion settings from it. On save, the new activity is automatically positioned immediately after the source Assignment in the same section, inherits its Restrict Access conditions, and the source Assignment is automatically hidden from students (left in place, not deleted). If the source Assignment uses a grading scale instead of points, the grade is left at the default rather than imported, since AI Proofreader only supports point grading.
- On the final-submission page, a student's prior online-text draft is now shown collapsed just under the assignment instructions, and the final submission's text box is prepopulated with that draft (kept in the rich text editor, not plain text) under a label reminding the student to actually revise it before submitting. Only applies to online-text drafts; file and Google Drive drafts are edited offline.
- The "AI notes on your revision" section is now expanded by default (previously collapsed) on both the student's page and the teacher's grading page.
- Site admin settings page for AI Proofreader: a warning banner and "Restore to default" button now appear above the "Default AI instructions" field whenever the saved instructions differ from the plugin's built-in default text.

### Changed
- The draft-vs-final AI comparison ("AI notes on your revision") now explicitly uses the same subject-matter-relevance standard as the original draft feedback when judging whether the final version addresses the assignment, instead of an independently-worded, stricter standard. Previously this could produce a harsher, seemingly contradictory verdict on the final version for writing that hadn't substantively changed from the draft.

### Fixed
- Completion tracking could incorrectly mark every student as complete regardless of actual submission status, on any activity using automatic completion. Caused by a missing `aiproofreader_get_coursemodule_info()` callback, which Moodle needs to recognize the "must make a final submission" rule as active; without it, Moodle saw zero active completion conditions and defaulted everyone to complete.
- The "AI Proofreader" activities list page (accessed via the Activities block) crashed with an exception, caused by instantiating the abstract core event class directly instead of a proper plugin-specific subclass.

## v0.3.4 (2026083101)
### Added
- `aiproofreader_get_anon_id($idnumber)`: computes a stable, one-way anonymous ID for a student from their Moodle `idnumber` (SSID), using a keyed HMAC-SHA256 hash. The same student always produces the same ID, with no mapping table stored anywhere - the secret key is generated automatically on first use, stored in site config, and never displayed or exported. Intended for use by `local_aiproofreaderreport`'s anonymized export.

## v0.3.3 (2026081103)
### Changed
- Scale radio layout changed from labeling each endpoint radio itself ("1 = Low" ... "5 = High", which visually separated the radio buttons) to plain "Low = 1 2 3 4 5 = High" - the Low/High labels now sit outside the group of radios as static text, so the five buttons stay adjacent.

## v0.3.2 (2026081102)
### Changed
- Student draft and final submission text boxes are now full Moodle text editors (Atto), not plain textareas - a much nicer typing/paste experience. Content is still converted to and stored as plain text under the hood, so AI prompts, sentence counting, and everything else downstream is unaffected.
- All 1-5 survey radio groups (student and teacher) now label the endpoints "1 = Low" and "5 = High" so respondents know which direction is positive, instead of showing bare unlabeled numbers.

### Added
- Draft submissions with an online text type now require at least 3 sentences (a simple terminal-punctuation heuristic). Submitting fewer re-displays the draft form with an explanatory error - the student never leaves the draft page. Only applies to the online text type; file uploads and Google Drive links aren't checked.

## v0.3.1 (2026081101)
### Added
- Group filter dropdown on the teacher's student submissions list (`view.php`), so long class lists can be narrowed to one course group at a time.

### Fixed
- Removed the independent "is this on-topic" AI check that ran alongside the draft/final comparison. It occasionally disagreed with the main comparison's own narrative (a false negative on a genuinely on-topic revision produced a contradictory note like "does not appear to address the assignment" directly followed by "fully addresses the assignment..."). The main comparison call's own score and summary are now trusted outright with no second-opinion override.

## v0.3.0 (2026081100)
### Added
- Two new columns on `aiproofreader_submission`: `feedbackaimodel` and `comparisonaimodel`, recording which AI model/provider generated the feedback and the comparison for that submission. Sourced from a new site-wide "Current AI model label" setting (edited from local_aiproofreaderreport) that you update whenever the site's AI provider/model configuration changes - e.g. each quarter - so survey results can be compared across models. If the AI provider itself happens to report a specific model identifier in its response, that's appended automatically as a bonus detail; most providers don't, so the manually maintained label is the reliable value either way.
- Privacy API updated to cover both new fields (metadata declaration and export).

## v0.2.1 (2026081001)
### Fixed
- `classes/privacy/provider.php`: interface list on the `provider` class was out of alphabetical order and mis-indented, and numerous multi-line database/writer calls didn't follow Moodle coding style. Reformatted throughout; no behavior changes.
- `index.php`: converted all legacy `array()` syntax to short array syntax `[]`, per Moodle coding guidelines.

## v0.2.0 (2026081000)
### Added
- Optional student and teacher surveys, off by default. When off, AI Proofreader runs in feedback-only mode with no survey questions shown to anyone - there is no way to see survey data without local_aiproofreaderreport installed, so nothing is collected until the survey system is explicitly turned on.
- Individual show/hide control per survey question (5 student, 6 teacher, plus each side's free-text box), and editable question wording. All controlled from local_aiproofreaderreport's new "AI Proofreader survey & data settings" page - not from this plugin's own settings.
- Google Doc text retention control: submitted Google Doc text is still fetched briefly so the AI can generate feedback and comparison, but is now cleared back to just the stored link once both AI steps finish, unless the site has opted in to retaining it (also controlled from local_aiproofreaderreport).

### Changed
- Student/teacher survey question columns (`aiproofreader_studentsurvey`, `aiproofreader_teachersurvey`) are no longer strictly required at the database level, since a disabled question is no longer collected.

### Fixed
- The "Default AI instructions" setting was registered under the wrong component name (`mod_aiproofreader` instead of `aiproofreader`), so it was never actually being read by the code that builds AI prompts - the setting silently had no effect. It now works as intended; **re-save this setting once after upgrading** to make sure the correct config value is in place.

## v0.1.6 (2026080706)
### Changed
- Removed the "Learning objectives" field - it was functionally redundant with the existing "Additional AI instructions" field, since both are teacher-only free text combined into the same AI prompt. Consolidated back into one field.

## v0.1.5 (2026080705)
### Added
- "Learning objectives" field (later removed in v0.1.6).

## v0.1.4 (2026080704)
### Added
- AI-generated 1-5 "Did student follow AI instructions" score, shown to the teacher on the grading page before the grade form. Backed by an independent yes/no topic-relevance check (separate from the narrative comparison call) so the score can't be thrown off by the same call also writing prose and picking a number.
- Grade-level-aware scoring: the AI is told to distinguish between feedback that was genuinely required (grammar errors, missing the assignment) versus optional depth/elaboration suggestions, so a young student who fixes every required issue isn't penalized for not adding elaboration beyond what's realistic for their age.

### Fixed
- AI comparison generation moved from an async, fire-and-forget trigger to synchronous (generated at the moment of final submission, before the redirect) - the async version had reliability issues where the comparison sometimes never generated at all.
- Google Drive submissions now actually fetch and use the document's real text content. Previously only the link was stored, meaning the AI was evaluating nothing for gdrive submissions.
- Google Drive link validation now happens at form-submission time (draft and final), blocking the submission with a clear error if the document isn't readable, instead of silently accepting it and only reporting the problem afterward.
- Off-topic detection significantly hardened: explicit instruction to judge topic relevance by subject matter only, ignoring spelling/grammar quality (a heavily misspelled but genuinely on-topic response was briefly being flagged as off-topic).
- Redirects in `view.php` moved to occur before any page output, fixing an issue where Moodle would fall back to a "click Continue" interstitial instead of a clean redirect.

## v0.1.3 (2026080703)
### Fixed
- Namespace bug (`html_writer` referenced without a leading backslash inside a namespaced class) causing a fatal error on the survey forms.

## v0.1.2 (2026080702)
### Added
- Grade category, Grade to pass, and "Hide grader identity from students" settings, matching core Assignment's Grade section (Type/Scale and Grading method intentionally excluded).

## v0.1.1 (2026080701)
### Changed
- Grade level and target Lexile merged into a single settings dropdown (e.g. "Grade 9 (Lexile 1050L)") instead of two separate fields.

## v0.1.0 (2026080700)
### Added
- Initial release: activity settings, capabilities, submission workflow (online text, Word file, or Google Drive link) for draft and final submissions, AI-generated Grammar and Spelling / Assignment Specifics feedback, required student and teacher surveys, teacher grading page, gradebook integration, course backup/restore, and course-reset support.
