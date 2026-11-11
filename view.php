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
require_once($CFG->libdir . '/completionlib.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);
// Module instance id.
$a  = optional_param('a', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('alfaview', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('alfaview', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($a) {
    $moduleinstance = $DB->get_record('alfaview', ['id' => $a], '*', MUST_EXIST);
    $course         = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('alfaview', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_alfaview');
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/alfaview:view', $modulecontext);

$event = \mod_alfaview\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('alfaview', $moduleinstance);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/alfaview/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$template = new stdClass();
if (empty($moduleinstance->room_settings_id)) {
    // Restored on a site whose alfaview account doesn't have the room.
    $template->roomMissing = true;
} else {
    // The join link is created on click (join.php), so it can't expire while the page is open.
    $template->joinLink = (new moodle_url('/mod/alfaview/join.php', ['id' => $cm->id, 'sesskey' => sesskey()]))->out(false);
    $template->webclientJoinLink = (new moodle_url(
        '/mod/alfaview/join.php',
        ['id' => $cm->id, 'client' => 'web', 'sesskey' => sesskey()]
    ))->out(false);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_alfaview/view', $template);
echo $OUTPUT->footer();
