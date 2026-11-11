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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_alfaview upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_alfaview_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor .

    if ($oldversion <= 2020063000) {
        unset_config('api_host', 'mod_alfaview');
    }

    if ($oldversion <= 2026011200) {
        unset_config('api_guest_code', 'mod_alfaview');
    }

    if ($oldversion < 2026102800) {
        // Until 0.4.0 everyone with mod/alfaview:addinstance joined with the moderator link. Give those roles
        // mod/alfaview:moderate, so custom roles outside its archetypes keep joining as moderators. The capability
        // has to be installed before it can be assigned; Moodle would only do that after the upgrade steps.
        update_capabilities('mod_alfaview');
        $grants = $DB->get_records_sql(
            'SELECT rc.id, rc.roleid, rc.contextid
               FROM {role_capabilities} rc
               JOIN {context} ctx ON ctx.id = rc.contextid
              WHERE rc.capability = ? AND rc.permission = ?',
            ['mod/alfaview:addinstance', CAP_ALLOW]
        );
        foreach ($grants as $grant) {
            assign_capability('mod/alfaview:moderate', CAP_ALLOW, $grant->roleid, $grant->contextid);
        }

        // Activity description (FEATURE_MOD_INTRO).
        $table = new xmldb_table('alfaview');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026102800, 'alfaview');
    }

    return true;
}
