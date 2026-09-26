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
 * Site-wide admin settings for mod_aiproofreader.
 *
 * @package    mod_aiproofreader
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $currentinstructions = get_config('aiproofreader', 'defaultaiinstructions');
    $defaultinstructions = get_string('defaultaiinstructions_default', 'aiproofreader');

    $normalizeforcompare = function ($text) {
        $text = str_replace("\r\n", "\n", (string) $text);
        return trim($text);
    };

    if (
        $currentinstructions !== false
        && trim((string) $currentinstructions) !== ''
        && $normalizeforcompare($currentinstructions) !== $normalizeforcompare($defaultinstructions)
    ) {
        $restoreurl = new moodle_url('/mod/aiproofreader/admin_restore_defaults.php', ['sesskey' => sesskey()]);
        $restorebutton = html_writer::link(
            $restoreurl,
            get_string('settings_restoredefault', 'aiproofreader'),
            [
                'class' => 'btn btn-sm ml-2',
                'style' => 'background-color:#a94442; color:#ffffff; border-color:#a94442;',
                'onclick' => 'return confirm(' . json_encode(get_string('settings_restoredefault_confirm', 'aiproofreader')) . ');',
            ]
        );
        $warninghtml = html_writer::div(
            get_string('settings_customizedwarning', 'aiproofreader') . ' ' . $restorebutton,
            'alert alert-warning'
        );

        $settings->add(new admin_setting_description(
            'aiproofreader/customizedwarning',
            '',
            $warninghtml
        ));
    }

    $settings->add(new admin_setting_configtextarea(
        'aiproofreader/defaultaiinstructions',
        get_string('defaultaiinstructions', 'aiproofreader'),
        get_string('defaultaiinstructions_desc', 'aiproofreader'),
        get_string('defaultaiinstructions_default', 'aiproofreader'),
        PARAM_RAW
    ));

    $settings->add(new admin_setting_heading(
        'aiproofreader/surveyheading',
        get_string('settings_surveyheading', 'aiproofreader'),
        get_string('settings_surveyheading_desc', 'aiproofreader')
    ));

    $settings->add(new admin_setting_heading(
        'aiproofreader/piiredactionheading',
        get_string('settings_piiredactionheading', 'aiproofreader'),
        get_string('settings_piiredactionheading_desc', 'aiproofreader')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiproofreader/piiredactionenabled',
        get_string('piiredactionenabled', 'aiproofreader'),
        get_string('piiredactionenabled_desc', 'aiproofreader'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/piiredactionbatchsize',
        get_string('piiredactionbatchsize', 'aiproofreader'),
        get_string('piiredactionbatchsize_desc', 'aiproofreader'),
        200,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'aiproofreader/gradelevelheading',
        get_string('settings_gradelevelheading', 'aiproofreader'),
        get_string('settings_gradelevelheading_desc', 'aiproofreader')
    ));

    $settings->add(new admin_setting_configselect(
        'aiproofreader/gradelevelsource',
        get_string('gradelevelsource', 'aiproofreader'),
        get_string('gradelevelsource_desc', 'aiproofreader'),
        'none',
        [
            'none' => get_string('gradelevelsource_none', 'aiproofreader'),
            'username' => get_string('gradelevelsource_username', 'aiproofreader'),
            'profilefield' => get_string('gradelevelsource_profilefield', 'aiproofreader'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/gradelevelprofilefield',
        get_string('gradelevelprofilefield', 'aiproofreader'),
        get_string('gradelevelprofilefield_desc', 'aiproofreader'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_heading(
        'aiproofreader/visionheading',
        get_string('settings_visionheading', 'aiproofreader'),
        get_string('settings_visionheading_desc', 'aiproofreader')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiproofreader/visionenabled',
        get_string('visionenabled', 'aiproofreader'),
        get_string('visionenabled_desc', 'aiproofreader'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/visionendpoint',
        get_string('visionendpoint', 'aiproofreader'),
        get_string('visionendpoint_desc', 'aiproofreader'),
        '',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'aiproofreader/visionapikey',
        get_string('visionapikey', 'aiproofreader'),
        get_string('visionapikey_desc', 'aiproofreader'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/visionmodel',
        get_string('visionmodel', 'aiproofreader'),
        get_string('visionmodel_desc', 'aiproofreader'),
        '',
        PARAM_RAW_TRIMMED,
        60
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/visionmaximages',
        get_string('visionmaximages', 'aiproofreader'),
        get_string('visionmaximages_desc', 'aiproofreader'),
        4,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'aiproofreader/visiontimeout',
        get_string('visiontimeout', 'aiproofreader'),
        get_string('visiontimeout_desc', 'aiproofreader'),
        180,
        PARAM_INT
    ));
}
