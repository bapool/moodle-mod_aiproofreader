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
$string['aiprompt_belowminimum'] = 'IMPORTANT: This draft is shorter than the minimum length the teacher set for this assignment (at least {$a}). In the ASSIGNMENT SPECIFICS section, tell the student clearly that their draft does not meet the minimum length and that they need to add more before their final submission.';
$string['aiprompt_changecheck_changed'] = 'The final version is different from the original draft.';
$string['aiprompt_changecheck_teacherfinalized'] = 'The student never made a final submission. At the end of the grading period the teacher closed the assignment and used the student\'s draft as the final version, so the final version is identical to the draft. State this plainly at the start of your SUMMARY (do not say the student chose not to revise). Then judge the draft as it stands: if the feedback found no significant spelling, grammar or assignment problems, say the draft was already acceptable and use a FOLLOWED SCORE of 4 or 5; if the feedback identified genuinely required corrections, say they were never made and use a FOLLOWED SCORE of 1 or 2.';
$string['aiprompt_changecheck_unchanged'] = 'The final version is word-for-word the same as the original draft - the student made no changes. Students are allowed to submit an unchanged draft, so this is not automatically a problem. State plainly at the start of your SUMMARY that no changes were made, then judge whether changes were actually needed: if the feedback found no significant spelling, grammar or assignment problems (only minor or optional suggestions), say the draft was already acceptable and use a FOLLOWED SCORE of 4 or 5; if the feedback identified genuinely required corrections, say they were not addressed and use a FOLLOWED SCORE of 1 or 2.';
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
{$a->final}

Automated change check (done by the software, not a guess): {$a->changecheck}';
$string['aiprompt_feedback'] = '{$a->defaultinstructions}

The student is in grade {$a->gradelevel}. Your feedback must not exceed a {$a->lexile}L reading level - treat this as a ceiling, not a target to approach. When in doubt, simplify further rather than less. Use short sentences, common everyday words, and one idea per sentence. If you must use a term the student may not know, define it in plain language right after using it.

Assignment instructions: {$a->activityinstructions}

Additional guidance from the teacher, not shown to the student: {$a->aiinstructions}

{$a->minimumnote}

Student draft:
{$a->studenttext}';
$string['aiprompt_imagesnote'] = 'The teacher included {$a} image(s) with the assignment instructions, attached to this message. Treat them as part of the assignment instructions.';
$string['aiproofreader:addinstance'] = 'Add a new AI Proofreader activity';
$string['aiproofreader:grade'] = 'Grade AI Proofreader submissions';
$string['aiproofreader:submit'] = 'Submit drafts and final work to an AI Proofreader activity';
$string['aiproofreader:view'] = 'View AI Proofreader activity';
$string['aiproofreader:viewallsubmissions'] = 'View all student submissions in an AI Proofreader activity';
$string['aiunknownerror'] = 'The AI request was not successful, and did not return an error message.';
$string['allowsubmissionsfromdate'] = 'Allow submissions from';
$string['assignmentname'] = 'Assignment name';
$string['availability'] = 'Availability';
$string['belowminimumfeedback'] = 'Your draft does not meet the minimum length for this assignment (at least {$a}). Please add more before you submit your final version.';
$string['cannotopendocx'] = 'Could not open the Word document.';
$string['cannotreadfile'] = 'Could not read the uploaded file.';
$string['changesnotchecked_file'] = 'Because you are uploading a file, AI Proofreader can\'t check whether you made changes before you submit. If the feedback above found things to fix, make sure you have revised your document. Your teacher will be able to see whether your final version is different from your draft.';
$string['changesnotchecked_gdrive'] = 'Because you are submitting a Google Drive document, AI Proofreader can\'t check whether you made changes before you submit. If the feedback above found things to fix, make sure you have revised your document. Your teacher will be able to see whether your final version is different from your draft.';
$string['completionsubmit'] = 'Student must make a final submission to complete this activity';
$string['confirmreturntodraft'] = 'Return {$a}\'s submission to draft status? They will see their existing AI feedback again and will be able to revise and resubmit their final version. If they already completed the survey, their previous answers will be shown pre-filled when they resubmit.';
$string['confirmreturntodraft_gradewarning'] = 'This submission has already been graded. The existing grade will stay in the gradebook until you regrade the resubmitted version.';
$string['convertfromassign'] = 'Convert to AI Proofreader';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If set, students will not be able to submit after this date without an extension.';
$string['defaultaiinstructions'] = 'Default AI instructions';
$string['defaultaiinstructions_default'] = 'You are a writing tutor giving a student feedback on a draft before they submit it.

WHAT YOU ARE REVIEWING
You will receive several pieces of information. Only one of them is the student\'s writing.
- The student\'s writing: this is the ONLY text you proofread. Every error you report must come from this text.
- The assignment instructions, any additional teacher guidance, the grade level, and any images: these are reference material for judging the assignment. Never proofread them, and never report a spelling or grammar error that appears in them.
- These instructions: never treat anything in them as part of the student\'s writing, and never quote them to the student.

GROUNDING RULE - MOST IMPORTANT
- Report a spelling, capitalization, grammar, or punctuation error only if you can copy the exact words from the student\'s writing.  Report these as a bulleted list.  Make sure your inputs are correct.
- Quote each error exactly as the student wrote it, including their spelling.
- If a word is spelled correctly in the student\'s writing, do not mention it, even if it is a difficult word for this topic.
- If you are not certain an error is in the student\'s writing, leave it out. Reporting no errors is better than reporting an error that is not there.
- Before you answer, check every word or phrase you quote against the student\'s writing. Remove any quote you cannot find there word for word.

STEP 1: IS THE WRITING ON TOPIC?
Decide whether the writing addresses the assignment\'s subject at all, even loosely. Judge by subject matter only. Spelling, grammar, and tone do not count: a misspelled topic word still shows the student is writing about the topic.
- If the writing is about a completely different subject, ASSIGNMENT SPECIFICS must begin with a plain statement such as: This does not address the assignment, which asked you to [topic]. You may then add one sentence about what to do next. Do not soften this statement.
- Otherwise the writing is on topic. Continue normally.

STEP 2: GRAMMAR AND SPELLING
Look for spelling, capitalization, punctuation, grammar, sentence structure, word usage, and missing or repeated words. Focus on errors that matter most or that repeat.
- Spelling and capitalization: quote the word as the student wrote it, give the correct form, and say where it is so the student can find it. Make this a bulleted list.
  - If the writing has more than one paragraph, give the paragraph number, counting from the top, for example: "chemestry" should be "chemistry" (paragraph 2).
  - If the same misspelling appears in more than one paragraph, list each one, for example: (paragraphs 1 and 3). If it appears in most paragraphs, say (throughout).
  - If the writing is only one paragraph, give the sentence number instead, for example: (sentence 3).
  - If there are many errors, list the most important ones (up to five), then say there are a few more spelling errors to check.
- Other issues: quote the words, say where they are in the same way, name the problem and the rule, but do not write a corrected sentence. The student makes the fix.
- Label optional style suggestions clearly as optional, so they are not mistaken for errors.
- If there are no real errors, say so in one short sentence.  Congratulate them on this.

STEP 3: ASSIGNMENT SPECIFICS
Use the assignment instructions, teacher guidance, grade level, and any images to judge how well the writing does what the assignment asks: whether requirements are met, ideas are clear, the writing is organized, ideas are developed enough for the grade level, and the writing stays focused.
- Teacher guidance is private. Never quote or list it. You may turn a relevant point into a suggestion without saying where it came from.
- Do not add facts, examples, evidence, or arguments for the student, and do not write missing parts for them.
- If the writing is on topic, begin with the single most important thing the student does well, stated warmly and specifically. Then give the one or two most important things to revise.

HOW TO WRITE THE FEEDBACK
- Speak to the student as "you". Never say "the student" or use their name.
- Match vocabulary to the grade level. For grade 3 or below, use short sentences and everyday words.
- Be encouraging and specific, but never hide that the writing is off topic.
- Do not grade, score, or rate the work. Do not rewrite the work.
- Keep the whole response under 175 words.

RESPONSE FORMAT
Use exactly these two headings, in this order, and nothing else:

GRAMMAR AND SPELLING:
No more than 75 words. A bulleted list of quoted misspellings with corrections.

ASSIGNMENT SPECIFICS:
No more than 100 words.';
$string['defaultaiinstructions_desc'] = 'The base instructions sent to the AI for every AI Proofreader activity on this site, describing the proofreading philosophy, what to review, and the exact response format. This is combined with each activity\'s own instructions and any additional AI instructions the teacher adds. If you change the two section header phrases (GRAMMAR AND SPELLING / ASSIGNMENT SPECIFICS), update them consistently - the plugin parses the AI response by looking for those exact phrases.';
$string['draftsubmissionheading'] = 'Submit your draft';
$string['duedate'] = 'Due date';
$string['earlygradenotice'] = 'Your current grade for this assignment is {$a->grade} / {$a->max}. You can still complete it - once you submit your final version, your teacher will regrade it.';
$string['earlygradesubmittednotice'] = 'Your final version has been submitted. Your current grade of {$a->grade} / {$a->max} will stay in place until your teacher regrades it.';
$string['err_cutoffdatebeforedue'] = 'Cut-off date must be after the due date.';
$string['err_duedatebeforeallow'] = 'Due date must be after the allow-submissions-from date.';
$string['err_gradeoutofrange'] = 'Grade must be between 0 and {$a}.';
$string['err_gradepassexceedsmax'] = 'Grade to pass cannot exceed the maximum points.';
$string['err_gradepassnumeric'] = 'Grade to pass must be a number.';
$string['err_gradepositive'] = 'Maximum points must be a positive number.';
$string['err_invalidgdrivelink'] = 'Please enter a valid Google Drive link.';
$string['err_mintextlength'] = 'Your draft needs at least {$a} before you can submit it for feedback.';
$string['err_mintextlengthfinal'] = 'Your final version needs at least {$a}.';
$string['err_nofileuploaded'] = 'Please upload a Word document.';
$string['err_nosubmissiontype'] = 'You must enable at least one submission type: online text, file, or Google Drive link.';
$string['err_notextentered'] = 'Please enter your text.';
$string['err_surveyrequired'] = 'Please answer all survey questions.';
$string['feedbackassignmentheading'] = 'Assignment Specifics';
$string['feedbackgrammarheading'] = 'Grammar and Spelling';
$string['feedbackheading'] = 'AI Feedback';
$string['fileuploadlabel'] = 'File (Word document)';
$string['finalbelowminimum'] = 'The final version is shorter than the minimum length for this assignment (at least {$a}).';
$string['finalchanged_no'] = 'No changes were made - the final version is the same as the draft. Students may submit an unchanged draft when the feedback found nothing significant to fix; see the AI comparison below.';
$string['finalchanged_teacherfinalized'] = 'The student did not make a final submission. You closed the assignment with "Finalize grade", so their draft is graded as the final version.';
$string['finalchanged_unknown'] = 'Could not be checked (the draft or final text is not available).';
$string['finalchanged_yes'] = 'Changes were made.';
$string['finalchangedheading'] = 'Changes from draft to final:';
$string['finalizeconfirm'] = 'Use this ONLY at the end of the grading period, when {$a} can no longer turn in work for a grade. Do not use it during the quarter while you can still collect assignments - use "Grade now" instead, which leaves the assignment open.<br><br>This will make {$a}\'s draft their final submission exactly as it is now, and close the assignment so they can no longer change it. You will then grade it and complete the teacher survey.';
$string['finalizeconfirmbutton'] = 'Finalize and grade';
$string['finalizeconfirmtitle'] = 'End of grading period only';
$string['finalizedone'] = '{$a}\'s draft is now their final submission and the assignment is closed. Enter the grade and complete the survey below.';
$string['finalizegrade'] = 'Finalize grade';
$string['finalizegradetooltip'] = 'End of grading period only: grade the student\'s draft as their final submission and close the assignment.';
$string['finalizenotavailable'] = 'Finalize grade is only available for a student who has submitted a draft, received feedback and not yet made a final submission.';
$string['finalsubmissionheading'] = 'Submit your final version';
$string['freetextlabel'] = 'Anything the AI feedback missed? (optional)';
$string['gdrivefetchfailed'] = 'We could not read the content of your Google Doc. In Google Docs, click Share, and under General access choose your school\'s name (anyone in your organization with the link) or "Anyone with the link", then try again.';
$string['gdrivefilelabel'] = 'Or select a Google Doc from your Drive';
$string['gdriveheading'] = 'Google Drive links';
$string['gdriveheading_desc'] = 'How AI Proofreader reads the content of Google Docs that students submit as a link.';
$string['gdriveissuer'] = 'Google OAuth 2 service';
$string['gdriveissuer_desc'] = 'AI Proofreader reads submitted Google Doc links while signed in as this OAuth 2 service\'s connected system account. Students then only need to share their doc with people in your organization, because the system account belongs to your Google Workspace domain. Docs set to "Restricted" still cannot be read. After choosing a service, reconnect its system account under Site administration > Server > OAuth 2 services so it is granted read-only Google Drive access. Choose "None" to read only docs shared as "Anyone with the link".';
$string['gdriveissuer_none'] = 'None (only docs shared as "Anyone with the link")';
$string['gdrivelinklabel'] = 'Google Drive link';
$string['generatingfeedback'] = 'Generating your feedback... this usually takes a few seconds.';
$string['generatingfeedbackerror'] = 'Something went wrong generating your feedback. Your draft has already been saved, so it is safe to leave this page and come back later, or try again below.';
$string['grade'] = 'Grade';
$string['gradedheading'] = 'Grade';
$string['gradeheader'] = 'Grade';
$string['gradelevel'] = 'Grade level';
$string['gradelevel_help'] = 'The grade level the AI should target when generating feedback. This also sets the target lexile measure used to gauge text complexity. For a new activity this defaults to the lowest grade among the students enrolled in the course.';
$string['gradelevelprofilefield'] = 'Grade level profile field';
$string['gradelevelprofilefield_desc'] = 'Short name of the custom user profile field that holds each student\'s grade (for example "9", "09", "Grade 9" or "K"). Only used when the source above is "Custom profile field".';
$string['gradelevelsource'] = 'Student grade level source';
$string['gradelevelsource_desc'] = 'Where AI Proofreader finds each student\'s grade level. When a teacher adds a new activity, the "Grade level" setting defaults to the lowest grade among the students enrolled in the course (grade 9 if no grades can be found).';
$string['gradelevelsource_none'] = 'None (always default to grade 9)';
$string['gradelevelsource_profilefield'] = 'Custom profile field';
$string['gradelevelsource_username'] = 'Graduation year at the start of the username (e.g. 27jsmith)';
$string['gradelexile'] = 'Grade {$a->grade} (Lexile {$a->lexile}L)';
$string['gradenotstudent'] = 'That user is not a student in this activity.';
$string['gradenotyetdue'] = 'Grade now becomes available after the due date, so students have the full time allowed to submit.';
$string['gradenow'] = 'Grade now';
$string['gradenowfeedbackpending'] = 'The student has submitted a draft, but its AI feedback hasn\'t been generated yet (it may still be in progress, or it failed and is waiting for the student to retry).';
$string['gradenowheading'] = 'Grade now: {$a}';
$string['gradenownotavailable'] = 'Grade now is only available for activities graded with points.';
$string['gradenownotice'] = '{$a->name} has not made a final submission yet (current stage: {$a->stage}). The grade you save here goes to the gradebook right away, but the assignment stays open: {$a->name} can still complete it. When they do, it will show as "Submitted after grading" so you can regrade it.';
$string['gradenowpreviousfinal'] = 'Previous final version (before it was returned to draft)';
$string['gradenowsaved'] = 'Grade saved. {$a} can still complete the assignment.';
$string['gradenowtooltip'] = 'Enter a grade before the student submits (for example a zero, or partial credit for a draft). The student can still complete the assignment.';
$string['gradeoutof'] = 'Grade out of {$a}';
$string['graderq1overallfeedback'] = 'Was the overall feedback useful?';
$string['graderq2specificfeedback'] = 'Was the assignment-specific feedback useful?';
$string['graderq3usedfeedback'] = 'Did the student use the feedback to improve their submission?';
$string['graderq4feedbackfollowed'] = 'Was the feedback followed?';
$string['graderq5aiscaffold'] = 'Did the AI help scaffold the student?';
$string['graderq6aiaccuracy'] = 'Was the AI feedback accurate for this assignment?';
$string['gradesaved'] = 'Grade saved.';
$string['gradetype'] = 'Grade type';
$string['gradetype_help'] = 'Point: the teacher enters a grade out of the maximum points, which goes to the gradebook. None: the activity isn\'t graded - the teacher still reviews the final version, adds comments and completes the survey, but nothing is sent to the gradebook.';
$string['gradetype_none'] = 'None';
$string['gradetype_point'] = 'Point';
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
$string['lastsubmitted'] = 'Last submitted';
$string['loadimportassign'] = 'Load';
$string['maximumgrade'] = 'Maximum points';
$string['messageprovider:returnedtodraft'] = 'Notification when a submission is returned to draft for revision';
$string['messageprovider:submittedaftergrading'] = 'Notification when a student submits their final version after it was already graded';
$string['minlength'] = 'Minimum length';
$string['minlength_help'] = 'The shortest draft a student can submit. Drafts typed into the text box can\'t be submitted until they reach this length. Google Drive and uploaded drafts are still accepted, but the student\'s AI feedback tells them the draft is too short. A paragraph counts when it has at least two sentences, so heading lines (name, teacher, class, date) and a title are not counted, and nothing after a "Works Cited", "Bibliography" or "References" heading is counted.';
$string['minlength_paragraphs'] = '{$a} paragraphs';
$string['minlength_sentences'] = '{$a} sentences';
$string['missingidandcmid'] = 'You must specify a course_module ID or an instance ID';
$string['modulename'] = 'AI Proofreader';
$string['modulename_help'] = 'The AI Proofreader activity lets students submit a draft, receive AI-generated feedback on grammar/spelling and assignment specifics, then submit a revised final version for teacher grading.';
$string['modulenameplural'] = 'AI Proofreaders';
$string['noaiproofreaders'] = 'There are no AI Proofreader activities in this course.';
$string['notextextracted'] = 'No text could be extracted from this {$a} file.';
$string['nothingtogradeyet'] = 'This student has not submitted a final version yet.';
$string['onlinetextlabel'] = 'Your text';
$string['onlinetextlabelfinal'] = 'Your initial draft should be edited according to the above feedback. Simply submitting the draft could result in a lowered final grade.';
$string['originalsubmission'] = 'Original:';
$string['piiredactionbatchsize'] = 'PII redaction batch size';
$string['piiredactionbatchsize_desc'] = 'Maximum number of draft submissions and, separately, final submissions the nightly PII redaction task will process in a single run. Keeps a large backlog from generating too many AI requests in one night; any submissions left over are simply picked up on the next run.';
$string['piiredactionenabled'] = 'Enable nightly PII redaction';
$string['piiredactionenabled_desc'] = 'When enabled, a nightly scheduled task sends any new draft/final submission text, AI feedback and AI comparison to the AI to create a de-identified copy, with personal names and other personal information replaced by generic placeholders. The original text is never changed or removed - the de-identified copy is stored separately, for safer use when releasing student writing publicly. AI-based redaction is not perfect and should still be spot-checked before anything is actually released.';
$string['piiredactionprompt'] = 'You are redacting personally identifiable information (PII) from a student\'s writing sample before it is used for research or public release. Missing a real person\'s name is a serious privacy failure; replacing an ordinary word by mistake is a minor one. When unsure whether a word is a person\'s name, redact it.

Redact ONLY the following:
1. Names of real, private people - the student, classmates, teachers, family members, friends, coaches, neighbors, or anyone else. Replace every first name (including nicknames and middle names) with the exact text "Fname" and every last name with the exact text "Lname". Redact BOTH parts of a full name: "Jordan Smith" becomes "Fname Lname". Keep titles but redact the name after them: "Mr. Smith" becomes "Mr. Lname", "Mrs. Jones" becomes "Mrs. Lname", "Coach Davis" becomes "Coach Lname". Redact possessives too: "Smith\'s" becomes "Lname\'s". Redact names wherever they appear, including the heading at the top of the paper (student name, teacher name), signatures, and names typed in all lowercase or all capitals.
2. Email addresses, replaced with "[email]".
3. Phone numbers, replaced with "[phone]".
4. Street addresses, replaced with "[address]".

Do NOT redact:
- Names of historical figures, authors, characters in books, or public figures referenced as part of the assignment\'s subject matter (for example, "George Washington", "Harper Lee", "Atticus Finch").
- The placeholders "Fname", "Lname" and "[link]", which may already appear in the text - leave them exactly as they are.
- Class names, school subjects, dates, place names (cities, states, countries) or the name of the school.

Do not change, correct, rephrase, summarize, or remove any other part of the text - preserve the original wording, spelling, grammar, punctuation, line breaks and formatting exactly as given, except for the redactions described above. Do not add any commentary, explanation, notes, headings, or quotation marks around the output. Output ONLY the redacted text.

TEXT TO REDACT:
{$a}';
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
$string['privacy:metadata:aiproofreader_submission:aicomparisonredacted'] = 'An AI-redacted, de-identified copy of the AI comparison, generated by the nightly PII redaction task for safe public release.';
$string['privacy:metadata:aiproofreader_submission:aifollowedscore'] = 'AI-generated 1-5 score of how well the student followed the feedback.';
$string['privacy:metadata:aiproofreader_submission:comparisonaimodel'] = 'Label identifying the AI model/provider that generated the comparison.';
$string['privacy:metadata:aiproofreader_submission:feedbackaimodel'] = 'Label identifying the AI model/provider that generated the feedback.';
$string['privacy:metadata:aiproofreader_submission:feedbackassignment'] = 'AI-generated Assignment Specifics feedback on the draft.';
$string['privacy:metadata:aiproofreader_submission:feedbackassignmentredacted'] = 'An AI-redacted, de-identified copy of the Assignment Specifics feedback, generated by the nightly PII redaction task for safe public release.';
$string['privacy:metadata:aiproofreader_submission:feedbackgrammar'] = 'AI-generated Grammar and Spelling feedback on the draft.';
$string['privacy:metadata:aiproofreader_submission:feedbackgrammarredacted'] = 'An AI-redacted, de-identified copy of the Grammar and Spelling feedback, generated by the nightly PII redaction task for safe public release.';
$string['privacy:metadata:aiproofreader_submission:finalchanged'] = 'Whether the final version differs from the draft.';
$string['privacy:metadata:aiproofreader_submission:finalgdrivelink'] = 'The student\'s final Google Drive link, if that submission type was used.';
$string['privacy:metadata:aiproofreader_submission:finaltext'] = 'The student\'s final submitted text.';
$string['privacy:metadata:aiproofreader_submission:finaltexthtml'] = 'The student\'s final submission with its formatting, as HTML.';
$string['privacy:metadata:aiproofreader_submission:finaltextredacted'] = 'An AI-redacted, de-identified copy of the student\'s final submission text, generated by the nightly PII redaction task for safe public release.';
$string['privacy:metadata:aiproofreader_submission:finaltimesubmitted'] = 'When the final version was submitted.';
$string['privacy:metadata:aiproofreader_submission:initialgdrivelink'] = 'The student\'s draft Google Drive link, if that submission type was used.';
$string['privacy:metadata:aiproofreader_submission:initialtext'] = 'The student\'s draft text.';
$string['privacy:metadata:aiproofreader_submission:initialtexthtml'] = 'The student\'s draft submission with its formatting, as HTML.';
$string['privacy:metadata:aiproofreader_submission:initialtextredacted'] = 'An AI-redacted, de-identified copy of the student\'s draft submission text, generated by the nightly PII redaction task for safe public release.';
$string['privacy:metadata:aiproofreader_submission:initialtimesubmitted'] = 'When the draft was submitted.';
$string['privacy:metadata:aiproofreader_submission:status'] = 'The submission workflow status.';
$string['privacy:metadata:aiproofreader_submission:teacherfinalized'] = 'Whether the teacher closed the assignment and used the draft as the final version.';
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
$string['privacy:metadata:aivision'] = 'When image input is turned on and an activity\'s instructions include images, AI Proofreader sends the AI prompt together with those images directly to the AI endpoint configured by the site administrator, instead of through Moodle\'s AI subsystem. No Moodle user identifier (name, email, or user ID) is included.';
$string['privacy:metadata:aivision:prompttext'] = 'The AI prompt: the activity instructions, the student\'s draft (and final) text, and the AI feedback already given.';
$string['privacy:metadata:core_ai'] = 'AI Proofreader sends the student\'s draft and final text to the site\'s configured AI provider to generate feedback and a comparison.';
$string['privacy:metadata:core_files'] = 'AI Proofreader stores uploaded Word document submissions using the Moodle file API.';
$string['privacy:metadata:core_message'] = 'AI Proofreader sends Moodle notifications (and, per each user\'s own notification preferences, an email): to a student when a teacher returns their submission to draft status, and to a teacher when a student they already graded makes a final submission. Each includes a link back to the activity.';
$string['privacy:metadata:google_drive'] = 'When a student submits their draft or final work as a Google Drive link, AI Proofreader sends the document\'s ID to Google to retrieve the document\'s content - as plain text for the AI, and as a Word copy used to show the teacher the student\'s formatting. If a Google OAuth 2 service is chosen in the plugin settings, the request is made through the Google Drive API signed in as the site\'s OAuth 2 system account (not the student\'s account); otherwise, or if that fails, it is made to Google Docs\' public export link. No Moodle user identifier (name, email, or user ID) of the student is sent to Google as part of this request - only the document ID taken from the link the student provided.';
$string['privacy:metadata:google_drive:docurl'] = 'The ID of the Google Doc the student submitted, taken from its URL and sent so its content can be retrieved.';
$string['q1overallfeedback'] = 'Was the overall feedback useful?';
$string['q2specificfeedback'] = 'Was the assignment-specific feedback useful?';
$string['q3usedfeedback'] = 'Did you use the feedback to improve your submission?';
$string['q4categoryhelped'] = 'Which feedback helped more?';
$string['q4categoryhelped_assignment'] = 'Assignment Specifics';
$string['q4categoryhelped_both'] = 'Both equally';
$string['q4categoryhelped_grammar'] = 'Grammar and Spelling';
$string['q5confidence'] = 'How confident are you in your final version compared to your draft?';
$string['readaloud'] = 'Read aloud';
$string['regrade'] = 'Regrade';
$string['resetsubmissions'] = 'Delete all AI Proofreader submissions, surveys, and grades';
$string['retrybutton'] = 'Retry generating feedback';
$string['returnedtodraft'] = '{$a}\'s submission has been returned to draft status.';
$string['returnedtodraftmessage_body'] = 'Your final submission for "{$a->activityname}" in {$a->coursename} has been returned to draft status by your teacher, so you can revise it before it\'s graded.

Please review the feedback and submit a revised final version when you\'re ready:
{$a->url}';
$string['returnedtodraftmessage_subject'] = '"{$a->activityname}" was returned to draft';
$string['returntodraft'] = 'Return to draft';
$string['reviewedheading'] = 'Teacher review';
$string['savegrade'] = 'Save grade';
$string['scalelabelhigh'] = '<span style="vertical-align: baseline;">&nbsp;&nbsp;High</span>';
$string['scalelabellow'] = '<span style="vertical-align: baseline;">Low&nbsp;&nbsp;</span>';
$string['settings_customizedwarning'] = 'You are using customized AI instructions instead of the site default.';
$string['settings_gradelevelheading'] = 'Default grade level';
$string['settings_gradelevelheading_desc'] = 'How the "Grade level" setting is pre-filled when a teacher adds a new activity.';
$string['settings_piiredactionheading'] = 'PII redaction';
$string['settings_piiredactionheading_desc'] = 'These settings control the nightly task that creates de-identified copies of student writing, ahead of any public release of that data.';
$string['settings_restoredefault'] = 'Restore to default';
$string['settings_restoredefault_confirm'] = 'Replace your customized AI instructions with the site default? This cannot be undone.';
$string['settings_surveyheading'] = 'Survey settings';
$string['settings_surveyheading_desc'] = 'The survey on/off switch, individual question wording, and which questions are collected are all configured from the AI Proofreader Report plugin (Site administration &rarr; Plugins &rarr; Local plugins &rarr; AI Proofreader Report settings), not here. That plugin is where survey data is also reported on, so its settings page keeps everything survey-related in one place.';
$string['settings_visionheading'] = 'Image input (vision model)';
$string['settings_visionheading_desc'] = 'Moodle\'s AI subsystem can only send text. When this is turned on and an activity\'s instructions or Additional files contain images (PNG, JPEG, GIF or WebP), AI Proofreader sends the prompt and the images directly to the OpenAI-compatible endpoint below, which must be serving a vision-language model. Activities without images, and any image request that fails, still go through Moodle\'s AI subsystem as usual. These direct requests are not recorded in Moodle\'s AI usage reports.';
$string['statusdraft'] = 'Not yet started';
$string['statusfeedbackpending'] = 'Generating feedback';
$string['statusfeedbackready'] = 'Draft submitted, awaiting revision';
$string['statusfinalsubmitted'] = 'Submitted final';
$string['statusgraded'] = 'Graded';
$string['statusgradedbeforesubmission'] = 'Graded before submission';
$string['statussubmittedaftergrading'] = 'Submitted after grading';
$string['stopreading'] = 'Stop reading';
$string['studentsurveyheading'] = 'Student\'s survey responses';
$string['submfile'] = 'File submission (Word documents only)';
$string['submgdrive'] = 'Google Drive link';
$string['submissiontype'] = 'Submission type';
$string['submissiontypes'] = 'Submission types';
$string['submitdraft'] = 'Submit draft for feedback';
$string['submitfinal'] = 'Submit final version';
$string['submitsurvey'] = 'Submit survey';
$string['submittedaftergradingmessage_body'] = '{$a->studentname} has submitted a final version of "{$a->activityname}" in {$a->coursename} after you had already entered a grade. Their current grade stays in the gradebook until you regrade it:
{$a->url}';
$string['submittedaftergradingmessage_subject'] = '{$a->studentname} submitted "{$a->activityname}" after grading';
$string['submittedaftergradingnotice'] = '{$a} made this final submission after a grade had already been entered. The earlier grade and comments are loaded below and stay in the gradebook until you save a new grade.';
$string['submtext'] = 'Online text';
$string['surveyheading'] = 'Before you submit...';
$string['surveythanks'] = 'Thank you - your survey answers have been saved.';
$string['task_redactpii'] = 'Redact PII from submission text';
$string['teacherfinalizednotice'] = 'This assignment is graded and closed. The grading period ended before you made a final submission, so your teacher graded your draft as your final version. It can no longer be changed.';
$string['teacherfinalizedsurveyprompt'] = 'You can still answer the survey below. Your answers help us improve AI Proofreader. They do not affect your grade.';
$string['teacherfreetextlabel'] = 'Any concerns about the AI feedback? (optional)';
$string['teacheroverviewheading'] = 'Student submissions';
$string['teachersurveyheading'] = 'Teacher survey (required to save the grade)';
$string['unsupportedfiletype'] = 'Unsupported file type: {$a}';
$string['updategrade'] = 'Update grade';
$string['visionapikey'] = 'API key';
$string['visionapikey_desc'] = 'Sent as a Bearer token. Leave empty if the endpoint doesn\'t need one.';
$string['visionenabled'] = 'Send images to the AI';
$string['visionenabled_desc'] = 'Send images from the activity instructions and Additional files to the vision model below.';
$string['visionendpoint'] = 'Chat completions URL';
$string['visionendpoint_desc'] = 'The full OpenAI-compatible chat completions URL, for example https://ai.example.org/v1/chat/completions. The host must not be on Moodle\'s cURL blocked hosts list.';
$string['visionmaximages'] = 'Maximum images per request';
$string['visionmaximages_desc'] = 'Images from the instructions are used first, then Additional files. Images are resized so their longest side is at most 1568 pixels.';
$string['visionmodel'] = 'Model name';
$string['visionmodel_desc'] = 'The model name exactly as the endpoint expects it, for example Qwen/Qwen3-VL-235B-A22B-Instruct-FP8. It must be a vision-language model.';
$string['visiontimeout'] = 'Timeout (seconds)';
$string['visiontimeout_desc'] = 'How long to wait for the AI to answer an image request before falling back to a text-only request.';
$string['waitingforgrade'] = 'Your final version has been submitted and is waiting to be graded.';
$string['waitingforreview'] = 'Your final version has been submitted and is waiting for your teacher to review it.';
$string['yourdraftheading'] = 'Your draft';
$string['yourgrade'] = 'Your grade: {$a->grade} / {$a->max}';
$string['yourstatus'] = 'Status';
