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
 * Prints an instance of mod_alfaview.
 *
 * @package     mod_alfaview
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course_module ID
$id = optional_param('id', 0, PARAM_INT);
// Module instance id
$a  = optional_param('a', 0, PARAM_INT);
// alfaview configuration
$config = get_config('mod_alfaview');

if ($id) {
    $cm             = get_coursemodule_from_id('alfaview', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('alfaview', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($a) {
    $moduleinstance = $DB->get_record('alfaview', array('id' => $a), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('alfaview', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', mod_alfaview));
}
$alfaview = $DB->get_record('alfaview_room_settings', array('id' => $moduleinstance->room_settings_id), '*', MUST_EXIST);


require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

$event = \mod_alfaview\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('alfaview', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/alfaview/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// determine whether current user qualifies as teacher
$qualifiesAsTeacher = has_capability('mod/alfaview:addinstance', $coursecontext);
if (!$qualifiesAsTeacher) {
    $qualifyingRoles = ['teacher', 'editingteacher', 'coursecreator', 'manager'];
    $qualifiesAsTeacher = false;
    $roles = get_user_roles($modulecontext, $USER->id);
    foreach ($roles as $role) {
        if (in_array($role->shortname, $qualifyingRoles)) {
            $qualifiesAsTeacher = true;
        }
    }
}

$api = new mod_alfaview_api();
$display_name = $USER->firstname . " " . $USER->lastname;
if ($qualifiesAsTeacher) {
    $joinLink = $api->createJoinLink($alfaview->teacher_id, $display_name, $alfaview->room_id);
} else {
    $joinLink = $api->createJoinLink($alfaview->student_id, $display_name, $alfaview->room_id);
}

$template = new stdClass();
$template->joinLink = $joinLink;

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_alfaview/view', $template);
echo $OUTPUT->footer();
