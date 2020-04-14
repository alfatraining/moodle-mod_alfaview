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

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function alfaview_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        default:
            return null;
    }
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
function alfaview_add_instance($moduleinstance, $mform = null)
{
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
function alfaview_update_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->room_settings_id = alfaview_manage_room_settings($moduleinstance->room_id);

    unset($moduleinstance->room_id);

    return $DB->update_record('alfaview', $moduleinstance);
}

/**
 * Removes an instance of the mod_alfaview from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function alfaview_delete_instance($id)
{
    global $DB;

    $exists = $DB->get_record('alfaview', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('alfaview', array('id' => $id));

    return true;
}

function alfaview_manage_room_settings($roomId)
{
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/alfaview/classes/api.php');

    $settings = $DB->get_record('alfaview_room_settings', ['room_id' => $roomId]);

    if (empty($settings)) {
        $api = new mod_alfaview_api();
        $settings = new stdClass();
        $settings->room_id = $roomId;
        $settings->teacher_id = $api->createTeacher($roomId);
        $settings->student_id = $api->createStudent($roomId);
        $id = $DB->insert_record('alfaview_room_settings', $settings);
    } else {
        $id = $settings->id;
    }

    return $id;
}
