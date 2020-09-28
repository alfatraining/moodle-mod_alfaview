<?php

// This file is part of the alfaview plugin for Moodle - http://moodle.org/
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
 * Plugin administration pages are defined here.
 *
 * @package     mod_alfaview
 * @category    admin
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/vendor/autoload.php');

if ($ADMIN->fulltree) {
    // init the settings page
    $name = 'modsettingalfaview';
    $title = get_string('pluginname', 'mod_alfaview');
    $settings = new admin_settingpage($name, $title);

    // settings page heading
    $name = 'mod_alfaview/settings_heading';
    $title = get_string('settings', 'mod_alfaview');
    $description = format_text(get_string('settings_desc', 'mod_alfaview'), FORMAT_MARKDOWN);
    $settings->add(new admin_setting_heading($name, $title, $description));

    // settings page description
    $name = 'mod_alfaview/api_description';
    $title = '';
    $description = format_text(get_string('api_description', 'mod_alfaview'));
    $settings->add(new admin_setting_description($name, $title, $description));

    // alfaview api client id
    $name = 'mod_alfaview/api_client_id';
    $title = get_string('api_client_id', 'mod_alfaview');
    $description = get_string('api_client_id_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $title, $description, $default));

    // alfaview api code
    $name = 'mod_alfaview/api_code';
    $title = get_string('api_code', 'mod_alfaview');
    $description = get_string('api_code_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configpasswordunmask($name, $title, $description, $default));

    // alfaview api company id
    $name = 'mod_alfaview/api_company_id';
    $title = get_string('api_company_id', 'mod_alfaview');
    $description = get_string('api_company_id_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $title, $description, $default));

    // test connection to alfaview
    if ($PAGE->url == $CFG->wwwroot . '/' . $CFG->admin . '/settings.php?section=modsettingalfaview') {
        $config = get_config('mod_alfaview');
        $testApiClientId = $config->api_client_id;
        $testApiCode = $config->api_code;
        $testApiCompanyId = $config->api_company_id;
        try {
            $av = new Alfaview\Alfaview();
            $credentials = new Alfaview\Model\AuthenticationAuthorizationCodeCredentials();
            $credentials->setClientId($testApiClientId);
            $credentials->setCode($testApiCode);
            $credentials->setCompanyId($testApiCompanyId);

            $response = $av->authenticate($credentials);
            if ($response->hasError) {
                $connection_status = $OUTPUT->notification(get_string('connection_status_error', 'mod_alfaview'), 'notifyproblem');
            } else {
                $permissions = $response->reply->getPermissions();
                if (is_array($permissions) && $permissions[5] && $permissions[15]) {
                    $connection_status = $OUTPUT->notification(get_string('connection_status_ok', 'mod_alfaview'), 'notifysuccess');
                } else {
                    $connection_status = $OUTPUT->notification(get_string('connection_status_error', 'mod_alfaview'), 'notifyproblem');
                }
            }
        } catch (moodle_exception $error) {
            $connection_status = $OUTPUT->notification(get_string('connection_status_error', 'mod_alfaview'), 'notifyproblem');
        }
        $name = 'mod_alfaview/api_status';
        $title = '';
        $settings->add(new admin_setting_description($name, $title, $connection_status));
    }
}
