# Changelog

All notable changes to AI Proofreader are documented here.

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
