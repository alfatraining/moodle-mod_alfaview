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
 * Creates a personal join link for an alfaview classroom and redirects to it.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course_module ID.
$id = required_param('id', PARAM_INT);
// Join with the alfaview app ('app') or in the browser ('web').
$client = optional_param('client', 'app', PARAM_ALPHA);
$webclient = $client === 'web';

$cm             = get_coursemodule_from_id('alfaview', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('alfaview', ['id' => $cm->instance], '*', MUST_EXIST);
$roomsettings   = $DB->get_record('alfaview_room_settings', ['id' => $moduleinstance->room_settings_id]);

require_login($course, true, $cm);
require_sesskey();

$modulecontext = context_module::instance($cm->id);
require_capability('mod/alfaview:view', $modulecontext);

$PAGE->set_url('/mod/alfaview/join.php', ['id' => $cm->id, 'client' => $client]);
$viewurl = new moodle_url('/mod/alfaview/view.php', ['id' => $cm->id]);

if (!$roomsettings) {
    redirect($viewurl, get_string('room_missing', 'mod_alfaview'), null, \core\output\notification::NOTIFY_ERROR);
}

// Moderators join with the moderator group link, everyone else as participant.
if (has_capability('mod/alfaview:moderate', $modulecontext)) {
    $accesskey = $roomsettings->teacher_id;
} else {
    $accesskey = $roomsettings->student_id;
}

$api = new mod_alfaview_api();
$displayname = $USER->firstname . " " . $USER->lastname;
try {
    $webclientblocked = $webclient && $api->is_webclient_blocked($roomsettings->room_id);
    if (!$webclientblocked) {
        $joinlink = $api->create_join_link($accesskey, $displayname, $roomsettings->room_id, $webclient);
    }
} catch (moodle_exception $e) {
    redirect($viewurl, get_string('join_link_create_error', 'mod_alfaview'), null, \core\output\notification::NOTIFY_ERROR);
}

if ($webclientblocked) {
    redirect($viewurl, get_string('webclient_blocked', 'mod_alfaview'), null, \core\output\notification::NOTIFY_ERROR);
}

// Moodle's redirect() strips URLs with unknown schemes such as alfaview://, so send the header directly.
// The web client link takes the same route, because it carries the alfaview:// link as a URL parameter.
\core\session\manager::write_close();
header('Location: ' . preg_replace('/[\x00-\x1F\x7F]/', '', $joinlink), true, 303);
die();
