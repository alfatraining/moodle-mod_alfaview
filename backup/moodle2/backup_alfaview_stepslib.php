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
 * Backup structure step for mod_alfaview.
 *
 * @package     mod_alfaview
 * @category    backup
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the structure of an alfaview activity backup.
 *
 * Only the alfaview room ID is backed up, not the group link access keys: anyone with the moderator key can join the
 * room as moderator, and backups can leave the site. Restore looks the keys up again (see restore_alfaview_stepslib.php).
 */
class backup_alfaview_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element The activity element wrapped in the standard activity structure.
     */
    protected function define_structure() {
        $alfaview = new backup_nested_element('alfaview', ['id'], ['name', 'intro', 'introformat', 'room_id']);

        $alfaview->set_source_sql(
            'SELECT a.id, a.name, a.intro, a.introformat, rs.room_id
               FROM {alfaview} a
          LEFT JOIN {alfaview_room_settings} rs ON rs.id = a.room_settings_id
              WHERE a.id = ?',
            [backup::VAR_ACTIVITYID]
        );

        $alfaview->annotate_files('mod_alfaview', 'intro', null);

        return $this->prepare_activity_structure($alfaview);
    }
}
