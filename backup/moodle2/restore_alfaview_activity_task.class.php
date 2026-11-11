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
 * Restore task for mod_alfaview.
 *
 * @package     mod_alfaview
 * @category    backup
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/backup/moodle2/restore_alfaview_stepslib.php');

/**
 * Provides the steps to restore one alfaview activity.
 */
class restore_alfaview_activity_task extends restore_activity_task {
    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the restore steps: alfaview only has one structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_alfaview_activity_structure_step('alfaview_structure', 'alfaview.xml'));
    }

    /**
     * Defines the contents in the activity that must be processed by the link decoder.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('alfaview', ['intro'], 'alfaview'),
        ];
    }

    /**
     * Defines the rules to decode the links encoded by backup_alfaview_activity_task::encode_content_links().
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('ALFAVIEWINDEX', '/mod/alfaview/index.php?id=$1', 'course'),
            new restore_decode_rule('ALFAVIEWVIEWBYID', '/mod/alfaview/view.php?id=$1', 'course_module'),
        ];
    }

    /**
     * Defines the rules to restore legacy log entries of the activity.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('alfaview', 'view', 'view.php?id={course_module}', '{alfaview}'),
        ];
    }

    /**
     * Defines the rules to restore legacy log entries of the course that belong to alfaview.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('alfaview', 'view all', 'index.php?id={course}', null),
        ];
    }
}
