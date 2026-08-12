# Changelog

All notable changes to AI Proofreader are documented here.

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
