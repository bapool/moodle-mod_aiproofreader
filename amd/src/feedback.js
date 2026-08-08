// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Auto-triggers AI feedback generation for mod_aiproofreader (so the page
 * never blocks a form POST on the AI server), and fixes the layout of the
 * category-preference survey question by working with the real DOM directly
 * instead of guessing Moodle's internal CSS class names.
 *
 * @module     mod_aiproofreader/feedback
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    var SILENT_RETRY_DELAY_MS = 1500;

    return {

        /**
         * Calls the AI to generate feedback for a pending draft, then reloads
         * the page if it succeeded. The very first call sometimes fails right
         * after the draft-save redirect, so one retry is attempted silently
         * before showing the student anything. Only a second failure reveals
         * the error UI - no time-based escalation, the student just waits.
         *
         * @param {Number} submissionId
         */
        generateFeedback: function(submissionId) {
            var statusDiv = document.getElementById('aiproofreader-feedback-status');
            var errorDetail = document.getElementById('aiproofreader-error-detail');

            var showError = function(message) {
                if (errorDetail) {
                    errorDetail.textContent = message || '';
                }
                if (statusDiv) {
                    statusDiv.classList.remove('d-none');
                }
            };

            var attempt = function(isRetry) {
                Ajax.call([{
                    methodname: 'mod_aiproofreader_generate_feedback',
                    args: {submissionid: submissionId}
                }])[0].done(function(response) {
                    if (response && response.success) {
                        window.location.reload();
                        return;
                    }
                    if (!isRetry) {
                        setTimeout(function() {
                            attempt(true);
                        }, SILENT_RETRY_DELAY_MS);
                        return;
                    }
                    showError(response && response.error);
                }).fail(function(ex) {
                    if (!isRetry) {
                        setTimeout(function() {
                            attempt(true);
                        }, SILENT_RETRY_DELAY_MS);
                        return;
                    }
                    Notification.exception(ex);
                    showError(ex && ex.message);
                });
            };

            attempt(false);
        },

        /**
         * Forces each option in the "Which feedback helped more?" question
         * onto its own row. Finds the radios by name (which we control and
         * is guaranteed correct) and styles their actual parent element
         * directly, rather than guessing Moodle's CSS class names - several
         * attempts at the CSS-only version were not reliable.
         */
        fixCategoryQuestionLayout: function() {
            var radios = document.querySelectorAll('input[type="radio"][name="q4categoryhelped"]');
            radios.forEach(function(radio) {
                if (radio.parentElement) {
                    radio.parentElement.style.display = 'block';
                }
            });
        }
    };
});
