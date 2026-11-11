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
 * Backup task for mod_alfaview.
 *
 * @package     mod_alfaview
 * @category    backup
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/alfaview/backup/moodle2/backup_alfaview_stepslib.php');

/**
 * Provides the steps to back up one alfaview activity.
 */
class backup_alfaview_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the backup steps: alfaview only has one structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_alfaview_activity_structure_step('alfaview_structure', 'alfaview.xml'));
    }

    /**
     * Encodes links to alfaview pages, so they can be restored to the new course.
     *
     * @param string $content Some HTML text that may contain links to alfaview pages.
     * @return string The content with links encoded.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/alfaview\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@ALFAVIEWINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/alfaview\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@ALFAVIEWVIEWBYID*$2@$', $content);

        return $content;
    }
}
