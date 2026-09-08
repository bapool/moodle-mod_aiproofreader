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
 * English language strings for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activityinstructions'] = 'Assignment instructions';
$string['additionalfiles'] = 'Additional files';
$string['additionalfiles_help'] = 'Optional. Attach any extra files students need for this assignment, such as a lab sheet or template. These are shown to students alongside the activity instructions.';
$string['aicomparisonheading'] = 'AI notes on your revision';
$string['aifeedbacksettings'] = 'AI Feedback';
$string['aifollowedscoreheading'] = 'Did student follow AI instructions:';
$string['aiinstructions'] = 'Additional AI instructions';
$string['aiinstructions_help'] = 'These instructions are sent to the AI along with the activity instructions above, but are never shown to students. Use this field to give the AI extra grading criteria or context - for example, the basics of a rubric, or a checklist of key concepts a strong response should demonstrate (such as what students should have learned from a lab). The AI may paraphrase relevant points from here to help scaffold the student, but will not quote or list this text directly to them.';
$string['aiprompt_comparison'] = 'You are helping a teacher assess whether a student meaningfully improved their writing between draft and final, based on the AI feedback they received. The student is in grade {$a->gradelevel}.

CRITICAL RULE - CHECK THIS FIRST: First compare the FINAL version to the ORIGINAL DRAFT to see whether the actual substance - the ideas, topic, and content the student wrote about - changed, or whether the student only fixed grammar, spelling, wording, or punctuation. If the substance is essentially the same as the draft, you must NOT re-decide from scratch whether the assignment was addressed - instead, use the same conclusion already reached in the assignment-specific feedback given below. If that feedback treated the draft as addressing the assignment topic (even if it suggested going further or adding more depth), then the final version addresses the assignment too, and the FOLLOWED SCORE must not be lowered to 1-2 for an \"off-topic\" reason - your SUMMARY should instead explicitly note that the student corrected the grammar/spelling issues that were identified. Only judge the FINAL version as not addressing the assignment - and only then consider a FOLLOWED SCORE of 1 or 2 for that reason - if the students actual subject matter genuinely changed to something unrelated to the assignment compared to the draft. When you do make that judgment, use the exact same subject-matter-only standard as the original feedback: ignore spelling/grammar, and only call it off-topic if it is genuinely unrelated, not merely underdeveloped. Never apply a stricter topic-relevance standard to the final version than was already applied to the draft.

SECOND RULE - WEIGH FEEDBACK BY WHAT IS REASONABLE FOR THIS GRADE LEVEL: Not all feedback is equally important to incorporate. Distinguish between feedback that pointed out something genuinely required (a grammar/spelling error, a missing core requirement, not addressing the assignment) versus feedback that suggested optional depth, elaboration, or polish beyond what is typically expected at this grade level. A young student who thoroughly fixes every grammar and spelling issue but does not add extra scientific or analytical depth beyond what a student their age would realistically produce should score HIGH (4-5), not be penalized for missing an "extra credit" level of elaboration. Only lower the score meaningfully for skipping feedback that was genuinely required, not for skipping suggestions that were realistically a stretch for the student age. Do not expect a young student to become more sophisticated than is developmentally realistic.

Respond in exactly this format, with nothing before or after:
FOLLOWED SCORE: (a single number from 1 to 5, using this scale: 5 = final version fully addresses the assignment AND the student incorporated the feedback to the extent reasonably expected for their grade level; 3 = final version addresses the assignment but skipped feedback that was genuinely required, not just optional depth; 1-2 = final version does not address the assignment at all, regardless of any other improvements)
SUMMARY: (In 3-5 sentences addressed to the teacher, briefly and constructively describe whether and how the feedback was applied. If the final version does not address the assignment, say so plainly here too.)

Assignment instructions:
{$a->activityinstructions}

Original draft:
{$a->draft}

Grammar and spelling feedback given:
{$a->feedbackgrammar}

Assignment-specific feedback given:
{$a->feedbackassignment}

Final version:
{$a->final}';
$string['aiprompt_feedback'] = '{$a->defaultinstructions}

The student is in grade {$a->gradelevel}, target reading level approximately {$a->lexile}L. Calibrate vocabulary, sentence complexity, and explanations to this level.

Assignment instructions: {$a->activityinstructions}

Additional guidance from the teacher, not shown to the student: {$a->aiinstructions}

Student draft:
{$a->studenttext}';
$string['aiproofreader:addinstance'] = 'Add a new AI Proofreader activity';
$string['aiproofreader:grade'] = 'Grade AI Proofreader submissions';
$string['aiproofreader:submit'] = 'Submit drafts and final work to an AI Proofreader activity';
$string['aiproofreader:view'] = 'View AI Proofreader activity';
$string['aiproofreader:viewallsubmissions'] = 'View all student submissions in an AI Proofreader activity';
$string['aiunknownerror'] = 'The AI request was not successful, and did not return an error message.';
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['assignmentname'] = 'Assignment name';
$string['availability'] = 'Availability';
$string['cannotopendocx'] = 'Could not open the Word document.';
$string['cannotreadfile'] = 'Could not read the uploaded file.';
$string['completionsubmit'] = 'Student must make a final submission to complete this activity';
$string['convertfromassign'] = 'Convert to AI Proofreader';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If set, students will not be able to submit after this date without an extension.';
$string['defaultaiinstructions'] = 'Default AI instructions';
$string['defaultaiinstructions_default'] = 'You are an AI proofreader helping a student improve their writing before submitting an assignment.

CRITICAL FIRST STEP - DO THIS BEFORE ANYTHING ELSE: Read the assignment instructions and the student\'s writing, then judge whether the writing addresses the assignment topic at all, even loosely. Judge this by SUBJECT MATTER ONLY - completely ignore spelling and grammar quality when making this determination. A student who writes "cemical reaction" clearly means "chemical reaction," and misspelled vocabulary or technical terms is a Grammar and Spelling issue, not evidence that the writing is off-topic. Never let poor spelling, informal tone, or grammar mistakes cause you to judge otherwise-relevant writing as not addressing the assignment. Only judge the writing as not addressing the assignment if its actual subject matter is genuinely unrelated to what the assignment asked for. If it truly does not address the assignment - for example, it is about a completely different subject entirely - your ASSIGNMENT SPECIFICS feedback below must lead with a direct, unambiguous statement of that fact, such as: "This does not address the assignment, which asked you to [topic]." State this plainly and factually. Do not soften it into language like "make sure you stay focused" or "consider addressing the assignment topic" - those phrasings imply the writing is on-topic but drifting, which is not the same as being about something else entirely, and do not bury this finding at the end of otherwise positive-sounding feedback. This determination overrides the encouraging tone described below - accuracy about whether the assignment was addressed always comes first.

The teacher will provide the student\'s grade level and the assignment instructions. Review the student\'s work based on their grade level and the specific assignment. Your feedback must be appropriate for the student\'s age and writing expectations.

IMPORTANT PROOFREADING PHILOSOPHY:

* Be encouraging, supportive, and respectful.
* Help the student improve their own writing rather than doing the work for them.
* Preserve the student\'s ideas, meaning, writing style, and individual voice.
* Use vocabulary and explanations appropriate for the student\'s grade level.
* Focus on the most useful improvements rather than identifying every minor issue.
* Do not grade, score, or assign a rating to the work.
* Do not rewrite the entire assignment.
* Do not add facts, arguments, evidence, examples, or ideas for the student.
* When possible, explain what needs improvement so the student can make the revision themselves.
* Be brief. Most students will not read long feedback - prioritize the one or two most important points over trying to cover everything.

Review the student\'s work in TWO MAIN AREAS:

1. GRAMMAR AND SPELLING

Review the writing for:

* Grammar
* Spelling
* Punctuation
* Capitalization
* Sentence structure
* Word usage
* Missing or repeated words
* Other mechanical writing errors

Focus on errors that are important or occur repeatedly. When helpful, show the student a specific example from their writing and explain how to correct it.

Clearly distinguish between an actual error and an optional suggestion for improving the writing.

2. ASSIGNMENT SPECIFICS

Use the teacher\'s assignment instructions, any additional guidance from the teacher, and the student\'s grade level to evaluate how well the writing addresses the assignment. If the teacher\'s additional guidance includes specific concepts the response should demonstrate understanding of, check whether the writing addresses at least one of them - this guidance is teacher-only and should never be quoted or listed directly to the student, but you may paraphrase a relevant point as a suggestion (for example, if the guidance mentions a scientific concept the student\'s writing does not address, you might suggest they consider explaining that concept, without saying where that suggestion came from).

Consider:

* Whether the student addressed the assignment requirements
* Whether the student\'s ideas are clear
* Whether the writing is appropriately organized
* Whether ideas are sufficiently explained or developed for the student\'s grade level
* Whether the student stays focused on the topic or purpose
* Whether transitions and the flow of ideas are effective
* Whether the writing style is appropriate for the type of assignment
* Whether important parts of the assignment appear to be missing or need more development

When something needs improvement, explain what the student should revisit or think about. Guide the student toward improving their own work rather than supplying the missing content for them.

PROVIDE YOUR RESPONSE IN THIS EXACT FORMAT:

GRAMMAR AND SPELLING:
[In 1-3 short sentences (no more than 60 words), point out only the most important grammar, spelling, punctuation, capitalization, or sentence-structure issues. If there are no significant issues, say so in one short sentence.]

ASSIGNMENT SPECIFICS:
[First, check: does this writing address the assignment topic at all, even loosely? Judge this by subject matter only - ignore spelling, grammar, and how polished the writing is. Misspelled vocabulary (like "cemical" for "chemical") still counts as addressing the topic. If it does NOT - for example, it is about a completely different subject - your response MUST begin with a direct, unambiguous sentence stating this plainly, such as: "This does not address the assignment, which asked you to [topic]." Do not soften this into vague language like "make sure you stay focused on the main topic" or "try to relate this back to the assignment" - those imply the writing is on-topic but drifting, which is not the same as being about something else entirely. State clearly and factually that the submission is off-topic. After that direct statement, you may add one brief sentence of guidance on what to do next. If the writing DOES address the assignment, your response MUST begin with the single most important thing the student is doing well, stated warmly and specifically - do not skip this positive opening or jump straight to what needs revision. After that, add the one or two most important things to revise. Keep this to 1-3 short sentences, no more than 100 words total.]

IMPORTANT FEEDBACK REMINDERS:

* Keep your total response under 175 words combined across both sections, but never sacrifice clarity for brevity - if the writing does not address the assignment at all, that must be stated plainly even if it takes a few extra words.
* Being encouraging does not mean softening or hedging on whether the writing addresses the assignment - state that plainly and directly even though you should otherwise be positive in tone.
* Always use exactly the two main sections shown above.
* Address the student directly using "you" throughout - never refer to them in the third person as "the student" or by name. For example, write "You did a great job explaining..." not "The student did a great job explaining..."
* Match your vocabulary and explanations to the student\'s grade level. For a young student (grade 3 or below), use short sentences and simple, everyday words - avoid academic or clinical-sounding phrasing like "demonstrates understanding" or "articulate" that would not sound natural said to a young child.
* Be positive and encouraging while still identifying meaningful improvements.
* Be specific. Refer to the student\'s actual writing whenever possible.
* Prioritize the most important improvements instead of overwhelming the student with minor corrections.
* Do not grade or score the assignment.
* Do not rewrite large portions of the student\'s work.
* Do not complete missing portions of the assignment for the student.
* Do not introduce new ideas, facts, evidence, or arguments for the student.
* Short examples of corrected grammar or sentence structure are allowed when they help teach the student how to fix an issue.
* Keep the feedback practical and focused on changes the student can make themselves.
* Your goal is to help the student produce a stronger final submission while learning how to improve their own writing.';
$string['defaultaiinstructions_desc'] = 'The base instructions sent to the AI for every AI Proofreader activity on this site, describing the proofreading philosophy, what to review, and the exact response format. This is combined with each activity\'s own instructions and any additional AI instructions the teacher adds. If you change the two section header phrases (GRAMMAR AND SPELLING / ASSIGNMENT SPECIFICS), update them consistently - the plugin parses the AI response by looking for those exact phrases.';
$string['draftsubmissionheading'] = 'Submit your draft';
$string['duedate'] = 'Due date';
$string['err_cutoffdatebeforedue'] = 'Cut-off date must be after the due date.';
$string['err_duedatebeforeallow'] = 'Due date must be after the allow-submissions-from date.';
$string['err_gradeoutofrange'] = 'Grade must be between 0 and {$a}.';
$string['err_gradepassexceedsmax'] = 'Grade to pass cannot exceed the maximum points.';
$string['err_gradepassnumeric'] = 'Grade to pass must be a number.';
$string['err_gradepositive'] = 'Maximum points must be a positive number.';
$string['err_invalidgdrivelink'] = 'Please enter a valid Google Drive link.';
$string['err_mintextlength'] = 'Your draft needs at least 3 sentences before you can submit it for feedback.';
$string['err_nochangesmade'] = 'It looks like no changes were made to your draft. Please revise your writing based on the feedback before submitting your final version.';
$string['err_nofileuploaded'] = 'Please upload a Word document.';
$string['err_nosubmissiontype'] = 'You must enable at least one submission type: online text, file, or Google Drive link.';
$string['err_notextentered'] = 'Please enter your text.';
$string['err_surveyrequired'] = 'Please answer all survey questions.';
$string['feedbackassignmentheading'] = 'Assignment Specifics';
$string['feedbackgrammarheading'] = 'Grammar and Spelling';
$string['feedbackheading'] = 'AI Feedback';
$string['fileuploadlabel'] = 'File (Word document)';
$string['finalsubmissionheading'] = 'Submit your final version';
$string['freetextlabel'] = 'Anything the AI feedback missed? (optional)';
$string['gdrivefetchfailed'] = 'We could not read the content of your Google Doc. Make sure it is shared as "Anyone with the link can view" (or comment/edit), then try again. In Google Docs: Share > General access > Anyone with the link.';
$string['gdrivefilelabel'] = 'Or select a Google Doc from your Drive';
$string['gdrivelinklabel'] = 'Google Drive link';
$string['generatingfeedback'] = 'Generating your feedback... this usually takes a few seconds.';
$string['generatingfeedbackerror'] = 'Something went wrong generating your feedback. Your draft has already been saved, so it is safe to leave this page and come back later, or try again below.';
$string['gradedheading'] = 'Grade';
$string['gradeheader'] = 'Grade';
$string['gradelevel'] = 'Grade level';
$string['gradelevel_help'] = 'The grade level the AI should target when generating feedback. This also sets the target lexile measure used to gauge text complexity.';
$string['gradelexile'] = 'Grade {$a->grade} (Lexile {$a->lexile}L)';
$string['graderq1overallfeedback'] = 'Was the overall feedback useful?';
$string['graderq2specificfeedback'] = 'Was the assignment-specific feedback useful?';
$string['graderq3usedfeedback'] = 'Did the student use the feedback to improve their submission?';
$string['graderq4feedbackfollowed'] = 'Was the feedback followed?';
$string['graderq5aiscaffold'] = 'Did the AI help scaffold the student?';
$string['graderq6aiaccuracy'] = 'Was the AI feedback accurate for this assignment?';
$string['gradesaved'] = 'Grade saved.';
$string['hidegrader'] = 'Hide grader identity from students';
$string['hidegrader_help'] = 'If enabled, students will not see which teacher graded their submission.';
$string['importcurrentsectiononlylabel'] = 'Only show assignments in this section';
$string['importcurrentsectiononlynote'] = 'After changing this, click Load to refresh the list below (you can leave the dropdown on "Choose..." just to refresh it).';
$string['importfromassign'] = 'Import from an existing Assignment';
$string['importfromassign_help'] = 'Optionally pick an existing Assignment activity in this course to prefill the name, description, dates, grade, and completion settings below. If the Assignment uses a grading scale rather than points, the maximum grade will not be imported - AI Proofreader only supports point grading, so please set it manually.';
$string['importfromassigndesc'] = 'Importing from an existing Moodle Assignment will copy its contents into this activity and move this activity directly under the copied Assignment once saved. It will also automatically hide the Assignment being copied from students.';
$string['importfromassignlabel'] = 'Assignment to import from';
$string['instructorcomments'] = 'Instructor comments';
$string['invaliddocx'] = 'This Word document could not be read.';
$string['loadimportassign'] = 'Load';
$string['maximumgrade'] = 'Maximum points';
$string['missingidandcmid'] = 'You must specify a course_module ID or an instance ID';
$string['modulename'] = 'AI Proofreader';
$string['modulename_help'] = 'The AI Proofreader activity lets students submit a draft, receive AI-generated feedback on grammar/spelling and assignment specifics, then submit a revised final version for teacher grading.';
$string['modulenameplural'] = 'AI Proofreaders';
$string['noaiproofreaders'] = 'There are no AI Proofreader activities in this course.';
$string['notextextracted'] = 'No text could be extracted from this {$a} file.';
$string['nothingtogradeyet'] = 'This student has not submitted a final version yet.';
$string['onlinetextlabel'] = 'Your text';
$string['onlinetextlabelfinal'] = 'Your initial draft should be edited according to the above feedback. Simply submitting the draft could result in a lowered final grade.';
$string['pluginadministration'] = 'AI Proofreader administration';
$string['pluginname'] = 'AI Proofreader';
$string['privacy:gradesgiven'] = 'Grades given as a teacher';
$string['privacy:metadata:aiproofreader_grade'] = 'The grade and instructor comments a teacher gave for a submission.';
$string['privacy:metadata:aiproofreader_grade:grade'] = 'The points awarded.';
$string['privacy:metadata:aiproofreader_grade:graderid'] = 'The ID of the teacher who gave this grade.';
$string['privacy:metadata:aiproofreader_grade:instructorcomments'] = 'The teacher\'s written comments.';
$string['privacy:metadata:aiproofreader_grade:timemodified'] = 'When the grade was last modified.';
$string['privacy:metadata:aiproofreader_studentsurvey'] = 'The student\'s required survey answers about the AI feedback, given before their final submission is accepted.';
$string['privacy:metadata:aiproofreader_studentsurvey:freetext'] = 'Optional free-text comment on what the AI feedback missed.';
$string['privacy:metadata:aiproofreader_studentsurvey:q1overallfeedback'] = 'Whether the overall feedback was useful (1-5).';
$string['privacy:metadata:aiproofreader_studentsurvey:q2specificfeedback'] = 'Whether the assignment-specific feedback was useful (1-5).';
$string['privacy:metadata:aiproofreader_studentsurvey:q3usedfeedback'] = 'Whether the student used the feedback to improve their submission (1-5).';
$string['privacy:metadata:aiproofreader_studentsurvey:q4categoryhelped'] = 'Which feedback category helped more.';
$string['privacy:metadata:aiproofreader_studentsurvey:q5confidence'] = 'Confidence in the final version compared to the draft (1-5).';
$string['privacy:metadata:aiproofreader_studentsurvey:timecreated'] = 'When the student survey was submitted.';
$string['privacy:metadata:aiproofreader_submission'] = 'A student\'s draft and final submission, AI feedback, and AI comparison for one AI Proofreader activity.';
$string['privacy:metadata:aiproofreader_submission:aicomparison'] = 'AI-generated comparison of the draft, feedback, and final version.';
$string['privacy:metadata:aiproofreader_submission:aifollowedscore'] = 'AI-generated 1-5 score of how well the student followed the feedback.';
$string['privacy:metadata:aiproofreader_submission:comparisonaimodel'] = 'Label identifying the AI model/provider that generated the comparison.';
$string['privacy:metadata:aiproofreader_submission:feedbackaimodel'] = 'Label identifying the AI model/provider that generated the feedback.';
$string['privacy:metadata:aiproofreader_submission:feedbackassignment'] = 'AI-generated Assignment Specifics feedback on the draft.';
$string['privacy:metadata:aiproofreader_submission:feedbackgrammar'] = 'AI-generated Grammar and Spelling feedback on the draft.';
$string['privacy:metadata:aiproofreader_submission:finalgdrivelink'] = 'The student\'s final Google Drive link, if that submission type was used.';
$string['privacy:metadata:aiproofreader_submission:finaltext'] = 'The student\'s final submitted text.';
$string['privacy:metadata:aiproofreader_submission:finaltimesubmitted'] = 'When the final version was submitted.';
$string['privacy:metadata:aiproofreader_submission:initialgdrivelink'] = 'The student\'s draft Google Drive link, if that submission type was used.';
$string['privacy:metadata:aiproofreader_submission:initialtext'] = 'The student\'s draft text.';
$string['privacy:metadata:aiproofreader_submission:initialtimesubmitted'] = 'When the draft was submitted.';
$string['privacy:metadata:aiproofreader_submission:status'] = 'The submission workflow status.';
$string['privacy:metadata:aiproofreader_submission:timecreated'] = 'When this submission record was created.';
$string['privacy:metadata:aiproofreader_submission:userid'] = 'The ID of the student who owns this submission.';
$string['privacy:metadata:aiproofreader_teachersurvey'] = 'A teacher\'s required survey answers about the AI feedback, given when grading a submission.';
$string['privacy:metadata:aiproofreader_teachersurvey:freetext'] = 'Optional free-text comment on concerns about the AI feedback.';
$string['privacy:metadata:aiproofreader_teachersurvey:graderid'] = 'The ID of the teacher who completed this survey.';
$string['privacy:metadata:aiproofreader_teachersurvey:q1overallfeedback'] = 'Whether the overall feedback was useful (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:q2specificfeedback'] = 'Whether the assignment-specific feedback was useful (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:q3usedfeedback'] = 'Whether the student used the feedback (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:q4feedbackfollowed'] = 'Whether the feedback was followed (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:q5aiscaffold'] = 'Whether the AI helped scaffold the student (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:q6aiaccuracy'] = 'Whether the AI feedback was accurate for this assignment (1-5).';
$string['privacy:metadata:aiproofreader_teachersurvey:timecreated'] = 'When the teacher survey was submitted.';
$string['privacy:metadata:core_ai'] = 'AI Proofreader sends the student\'s draft and final text to the site\'s configured AI provider to generate feedback and a comparison.';
$string['privacy:metadata:core_files'] = 'AI Proofreader stores uploaded Word document submissions using the Moodle file API.';
$string['q1overallfeedback'] = 'Was the overall feedback useful?';
$string['q2specificfeedback'] = 'Was the assignment-specific feedback useful?';
$string['q3usedfeedback'] = 'Did you use the feedback to improve your submission?';
$string['q4categoryhelped'] = 'Which feedback helped more?';
$string['q4categoryhelped_assignment'] = 'Assignment Specifics';
$string['q4categoryhelped_both'] = 'Both equally';
$string['q4categoryhelped_grammar'] = 'Grammar and Spelling';
$string['q5confidence'] = 'How confident are you in your final version compared to your draft?';
$string['readaloud'] = 'Read aloud';
$string['resetsubmissions'] = 'Delete all AI Proofreader submissions, surveys, and grades';
$string['retrybutton'] = 'Retry generating feedback';
$string['savegrade'] = 'Save grade';
$string['scalelabelhigh'] = '<span style="vertical-align: baseline;">&nbsp;&nbsp;High</span>';
$string['scalelabellow'] = '<span style="vertical-align: baseline;">Low&nbsp;&nbsp;</span>';
$string['settings_customizedwarning'] = 'You are using customized AI instructions instead of the site default.';
$string['settings_restoredefault'] = 'Restore to default';
$string['settings_restoredefault_confirm'] = 'Replace your customized AI instructions with the site default? This cannot be undone.';
$string['settings_surveyheading'] = 'Survey settings';
$string['settings_surveyheading_desc'] = 'The survey on/off switch, individual question wording, and which questions are collected are all configured from the AI Proofreader Report plugin (Site administration &rarr; Plugins &rarr; Local plugins &rarr; AI Proofreader Report settings), not here. That plugin is where survey data is also reported on, so its settings page keeps everything survey-related in one place.';
$string['statusdraft'] = 'Not yet started';
$string['statusfeedbackpending'] = 'Generating feedback';
$string['statusfeedbackready'] = 'Feedback ready, awaiting final submission';
$string['statusfinalsubmitted'] = 'Submitted, awaiting grade';
$string['statusgraded'] = 'Graded';
$string['stopreading'] = 'Stop reading';
$string['studentsurveyheading'] = 'Student\'s survey responses';
$string['submfile'] = 'File submission (Word documents only)';
$string['submgdrive'] = 'Google Drive link';
$string['submissiontype'] = 'Submission type';
$string['submissiontypes'] = 'Submission types';
$string['submitdraft'] = 'Submit draft for feedback';
$string['submitfinal'] = 'Submit final version';
$string['submtext'] = 'Online text';
$string['surveyheading'] = 'Before you submit...';
$string['teacherfreetextlabel'] = 'Any concerns about the AI feedback? (optional)';
$string['teacheroverviewheading'] = 'Student submissions';
$string['teachersurveyheading'] = 'Teacher survey (required to save the grade)';
$string['unsupportedfiletype'] = 'Unsupported file type: {$a}';
$string['waitingforgrade'] = 'Your final version has been submitted and is waiting to be graded.';
$string['yourdraftheading'] = 'Your draft';
$string['yourgrade'] = 'Your grade: {$a->grade} / {$a->max}';
$string['yourstatus'] = 'Status';
