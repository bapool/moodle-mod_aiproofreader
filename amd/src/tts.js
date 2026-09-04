// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * "Read aloud" buttons for AI feedback sections in mod_aiproofreader, using
 * the browser's built-in text-to-speech (Web Speech API) - no server-side
 * audio generation or AI provider involved.
 *
 * @module     mod_aiproofreader/tts
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var PLAYING_CLASS = 'aiproofreader-tts-playing';
    var currentButton = null;

    /**
     * Resets a button back to its default "Read aloud" appearance.
     *
     * @param {HTMLElement} button
     */
    var resetButton = function(button) {
        button.classList.remove(PLAYING_CLASS);
        var icon = button.querySelector('.aiproofreader-tts-icon');
        var label = button.querySelector('.aiproofreader-tts-label');
        if (icon) {
            icon.classList.remove('fa-stop');
            icon.classList.add('fa-volume-up');
        }
        if (label) {
            label.textContent = button.dataset.ttsLabelPlay;
        }
    };

    /**
     * Switches a button into its "Stop" appearance while speaking.
     *
     * @param {HTMLElement} button
     */
    var setButtonPlaying = function(button) {
        button.classList.add(PLAYING_CLASS);
        var icon = button.querySelector('.aiproofreader-tts-icon');
        var label = button.querySelector('.aiproofreader-tts-label');
        if (icon) {
            icon.classList.remove('fa-volume-up');
            icon.classList.add('fa-stop');
        }
        if (label) {
            label.textContent = button.dataset.ttsLabelStop;
        }
    };

    return {

        /**
         * Wires up every .aiproofreader-tts-button on the page. Safe to call
         * even when no such buttons are present, and hides the buttons
         * entirely if the browser has no speech synthesis support.
         */
        init: function() {
            if (!('speechSynthesis' in window)) {
                document.querySelectorAll('.aiproofreader-tts-button').forEach(function(button) {
                    button.classList.add('d-none');
                });
                return;
            }

            document.querySelectorAll('.aiproofreader-tts-button').forEach(function(button) {
                button.addEventListener('click', function() {
                    // Clicking the button that is already playing just stops it.
                    if (button === currentButton) {
                        window.speechSynthesis.cancel();
                        resetButton(button);
                        currentButton = null;
                        return;
                    }

                    // Starting a new one always stops whatever was playing.
                    window.speechSynthesis.cancel();
                    if (currentButton) {
                        resetButton(currentButton);
                    }

                    var utterance = new SpeechSynthesisUtterance(button.dataset.ttsText || '');
                    utterance.addEventListener('end', function() {
                        resetButton(button);
                        if (currentButton === button) {
                            currentButton = null;
                        }
                    });
                    utterance.addEventListener('error', function() {
                        resetButton(button);
                        if (currentButton === button) {
                            currentButton = null;
                        }
                    });

                    currentButton = button;
                    setButtonPlaying(button);
                    window.speechSynthesis.speak(utterance);
                });
            });
        }
    };
});
