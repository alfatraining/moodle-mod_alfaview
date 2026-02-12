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

use Alfaview\Client\Model\APICredentials;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/vendor/autoload.php');

if ($ADMIN->fulltree) {
    // Settings page heading.
    $name = 'mod_alfaview/settings_heading';
    $title = get_string('settings', 'mod_alfaview');
    $description = format_text(get_string('settings_desc', 'mod_alfaview'), FORMAT_MARKDOWN);
    $settings->add(new admin_setting_heading($name, $title, $description));

    // Settings page description.
    $name = 'mod_alfaview/api_description';
    $title = '';
    $description = format_text(get_string('api_description', 'mod_alfaview'));
    $settings->add(new admin_setting_description($name, $title, $description));

    // Alfaview api client id.
    $name = 'mod_alfaview/api_client_id';
    $title = get_string('api_client_id', 'mod_alfaview');
    $description = get_string('api_client_id_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $title, $description, $default));

    // Alfaview api code.
    $name = 'mod_alfaview/api_code';
    $title = get_string('api_code', 'mod_alfaview');
    $description = get_string('api_code_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configpasswordunmask($name, $title, $description, $default));

    // Alfaview api company id.
    $name = 'mod_alfaview/api_company_id';
    $title = get_string('api_company_id', 'mod_alfaview');
    $description = get_string('api_company_id_desc', 'mod_alfaview');
    $default = '';
    $settings->add(new admin_setting_configtext($name, $title, $description, $default));

    // Test connection to alfaview.
    if ($PAGE->url == $CFG->wwwroot . '/' . $CFG->admin . '/settings.php?section=modsettingalfaview') {
        $config = get_config('mod_alfaview');
        $testapiclientid = $config->api_client_id;
        $testapicode = $config->api_code;
        $testapicompanyid = $config->api_company_id;

        $connectionstatus = '';

        try {
            $av = new Alfaview\Client\Alfaview();
            $credentials = new APICredentials();
            $credentials->setClientId($testapiclientid);
            $credentials->setKey($testapicode);
            $credentials->setCompanyId($testapicompanyid);

            $response = $av->authenticationApi->authenticateAPIKey($credentials);
            $accesstoken = $response->getAccessToken();

            $tokeninfo = $av->authenticationApi->getTokenInfo($accesstoken);
            if ($tokeninfo->getPermissions()->getRoomAdmin()) {
                $connectionstatus = $OUTPUT->notification(
                    get_string('connection_status_ok', 'mod_alfaview'),
                    'notifysuccess'
                );
            } else {
                $connectionstatus = $OUTPUT->notification(
                    get_string('connection_status_error', 'mod_alfaview'),
                    'notifyproblem'
                );
            }
        } catch (\Exception $error) {
            $connectionstatus = $OUTPUT->notification(
                get_string('connection_status_error', 'mod_alfaview', $error->getMessage()),
                'notifyproblem'
            );
        }

        $name = 'mod_alfaview/api_status';
        $title = '';
        $settings->add(new admin_setting_description($name, $title, $connectionstatus));
    }
}
