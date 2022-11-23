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
 * Plugin strings are defined here.
 *
 * @package     mod_alfaview
 * @category    string
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginadministration'] = 'Manage alfaview';
$string['pluginname'] = 'alfaview';
$string['settings'] = 'Configuration';
$string['settings_desc'] = 'Configure alfaview';
$string['api_description'] = 'API information is available in the alfaview admin app, under <a href="https://app.alfaview.com/#/settings/api-keys" target="_blank">Account Management</a>.';
$string['api_client_id'] = 'Alias (Client ID)';
$string['api_client_id_desc'] = 'The username of your alfaview API credentials.';
$string['api_code'] = 'Secret';
$string['api_code_desc'] = 'The password of your alfaview API credentials.';
$string['api_company_id'] = 'Account ID';
$string['api_company_id_desc'] = 'The account identifier of your alfaview API credentials.';
$string['connection_status_ok'] = 'Connection successful.';
$string['connection_status_error'] = 'Connection failed, please check that you entered the correct values and that the user who created the API key has permissions to administrate user and rooms.';

$string['modulename'] = 'alfaview classroom';
$string['modulenameplural'] = 'alfaview classrooms';
$string['modulename_help'] = 'Conduct reliable, high quality online classes with the alfaview video conferencing technology.';

$string['room_id'] = 'Select a room';
$string['room_select'] = 'Search a room';
$string['room_select_help'] = 'Go to <a href="https://app.alfaview.com/" target="_blank">your alfaview account</a> to create more rooms.';

$string['join_button_label'] = 'Join classroom';
$string['join_help_download'] = 'Make sure alfaview is installed. Download <a href="https://alfaview.com/download" target="_blank">here</a>.';
$string['join_help_support'] = 'Need more help? Visit the <a href="http://support.alfaview.com" target="_blank">alfaview support center</a>.';

$string['alfaview:view'] = 'alfaview classroom';
$string['alfaview:addinstance'] = 'Add alfaview classroom';

$string['privacy:metadata:alfaview'] = 'In order to join an alfaview classroom, a participant needs to provide a display name. alfaview uses the first and last name property of a Moodle user and concatenates them into a single string. This string is passed on to the meeting room server and vaporizes one hour after leaving the meeting room.';
$string['privacy:metadata:alfaview:display_name'] = 'The display name of a participant inside an alfaview classroom';
