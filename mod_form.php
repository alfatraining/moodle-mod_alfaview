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
 * The main mod_alfaview configuration form.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_alfaview
 * @copyright  alfatraining Bildungszentrum GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_alfaview_mod_form extends moodleform_mod
{

    /**
     * Defines forms elements
     */
    public function definition()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/alfaview/classes/api.php');
        $api = new mod_alfaview_api();
        $rooms = $api->listRooms();

        if (isset($this->current->room_settings_id)) {
            $settings = $DB->get_record('alfaview_room_settings', ['id' => $this->current->room_settings_id]);
        }

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Add alfaview room id
        $options = array();
        $options[] = '';
        foreach ($rooms as $id => $room) {
            $options[$id] = $room->getDisplayName();
        }
        asort($options);
        $autocompleteOpts = ['placeholder' => get_string('room_select', 'mod_alfaview')];
        $roomSelector = $mform->addElement('autocomplete', 'room_id', get_string('room_id', 'mod_alfaview'), $options, $autocompleteOpts);
        $mform->addRule('room_id', null, 'required', null, 'client');
        $mform->addRule('room_id', get_string('maximumchars', '', 36), 'maxlength', 36, 'client');
        $mform->addElement('static', 'add_room_help', '', get_string('room_select_help', 'mod_alfaview'));

        if (isset($settings) && isset($settings->room_id)) {
            $roomSelector->setSelected($settings->room_id);
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
