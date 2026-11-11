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
 * Restore structure step for mod_alfaview.
 *
 * @package     mod_alfaview
 * @category    backup
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores one alfaview activity.
 */
class restore_alfaview_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the paths in the backup file to process.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [new restore_path_element('alfaview', '/activity/alfaview')];

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Creates the alfaview record and links it to the room settings of the backed up room.
     *
     * @param array $data The alfaview element from the backup.
     */
    protected function process_alfaview($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->room_settings_id = $this->get_room_settings_id($data->room_id ?? '');
        unset($data->room_id);

        $newitemid = $DB->insert_record('alfaview', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Finds or creates the room settings for an alfaview room.
     *
     * On the same site (course copy, import, restore) the room settings already exist and are reused. On another site
     * new group links are created through the alfaview API. If that fails, for example because the room belongs to a
     * different alfaview account, the activity is restored without a room and a teacher has to select one.
     *
     * @param string $roomid The alfaview room ID from the backup.
     * @return int The room settings ID, or 0 if the activity has no room.
     */
    private function get_room_settings_id($roomid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/alfaview/lib.php');

        if (empty($roomid)) {
            return 0;
        }

        $settingsid = $DB->get_field('alfaview_room_settings', 'id', ['room_id' => $roomid]);
        if ($settingsid) {
            return $settingsid;
        }

        try {
            return alfaview_manage_room_settings($roomid);
        } catch (moodle_exception $e) {
            $this->log("alfaview room $roomid could not be set up, the activity has to be linked to a room again: " .
                $e->getMessage(), backup::LOG_WARNING);
            return 0;
        }
    }

    /**
     * Restores the files of the activity description.
     */
    protected function after_execute() {
        $this->add_related_files('mod_alfaview', 'intro', null);
    }
}
