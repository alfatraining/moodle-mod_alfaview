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
 * Library of interface functions and constants.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return mixed True or a feature value if the feature is supported, null otherwise.
 */
function alfaview_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        default:
            return null;
    }
}

/**
 * Whether the activity is branded.
 *
 * Moodle then shows the colored alfaview icon instead of tinting it with the activity purpose color.
 *
 * @return bool True if the activity is branded, false otherwise.
 */
function alfaview_is_branded(): bool {
    return true;
}

/**
 * Saves a new instance of the mod_alfaview into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_alfaview_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function alfaview_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->room_settings_id = alfaview_manage_room_settings($moduleinstance->room_id);

    unset($moduleinstance->room_id);

    return $DB->insert_record('alfaview', $moduleinstance);
}

/**
 * Updates an instance of the mod_alfaview in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_alfaview_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function alfaview_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->room_settings_id = alfaview_manage_room_settings($moduleinstance->room_id);

    unset($moduleinstance->room_id);

    $oldroomsettingsid = $DB->get_field('alfaview', 'room_settings_id', ['id' => $moduleinstance->id]);
    $DB->update_record('alfaview', $moduleinstance);

    if ($oldroomsettingsid != $moduleinstance->room_settings_id) {
        alfaview_delete_unused_room_settings($oldroomsettingsid);
    }

    return true;
}

/**
 * Removes an instance of the mod_alfaview from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function alfaview_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('alfaview', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('alfaview', ['id' => $id]);
    alfaview_delete_unused_room_settings($exists->room_settings_id);

    return true;
}

/**
 * Deletes room settings and their group links in alfaview once no activity uses them anymore.
 *
 * If none of the group links can be deleted, the room settings are kept: the links still work and are reused when the
 * room is selected again. If only some are deleted, the room settings are deleted anyway, because a moderator or
 * participant key without its link can't be used to join. Selecting the room again then creates new links.
 *
 * @param int $roomsettingsid The room settings ID.
 */
function alfaview_delete_unused_room_settings($roomsettingsid) {
    global $CFG, $DB;

    if (empty($roomsettingsid) || $DB->record_exists('alfaview', ['room_settings_id' => $roomsettingsid])) {
        return;
    }

    $settings = $DB->get_record('alfaview_room_settings', ['id' => $roomsettingsid]);
    if (!$settings) {
        return;
    }

    require_once($CFG->dirroot . '/mod/alfaview/classes/api.php');
    try {
        $api = new mod_alfaview_api();
        $complete = $api->delete_group_links($settings->room_id, [$settings->teacher_id, $settings->student_id]);
    } catch (moodle_exception $e) {
        debugging('alfaview group links of room ' . $settings->room_id . ' were not deleted: ' . $e->getMessage());
        return;
    }

    if (!$complete) {
        debugging('Some alfaview group links of room ' . $settings->room_id . ' were not deleted.');
    }

    $DB->delete_records('alfaview_room_settings', ['id' => $roomsettingsid]);
}

/**
 * Manages room settings for alfaview.
 *
 * Creates or retrieves room settings including teacher and student IDs.
 *
 * @param string $roomid The room ID.
 * @return string The settings record ID.
 */
function alfaview_manage_room_settings($roomid) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/alfaview/classes/api.php');

    $settings = $DB->get_record('alfaview_room_settings', ['room_id' => $roomid]);

    if (empty($settings)) {
        $api = new mod_alfaview_api();
        $settings = new stdClass();
        $settings->room_id = $roomid;
        $settings->teacher_id = $api->create_teacher($roomid);
        $settings->student_id = $api->create_student($roomid);
        $id = $DB->insert_record('alfaview_room_settings', $settings);
    } else {
        $id = $settings->id;
    }

    return $id;
}
