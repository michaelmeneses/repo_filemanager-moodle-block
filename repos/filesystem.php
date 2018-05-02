<?php
// This file is part of Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

require_once('generic.php');

/**
 * Generic implementation for a remote manageable Moodle repository type, this will translate the json strings into
 * the expected format used by the repo file manager and allow all repositories on the system to be browsed
 * @author Tim Williams (tmw@autotrain.org) for EA LLP 2010
 * @licence GPL v3
 *
 */
class block_repofile_filesystem extends block_repofile_generic {

    public function process_directory_direnntry($item) {
        $entry = new stdclass();
        $entry->name = $item['title'];
        $entry->filedate = $item['date'];
        $uns = unserialize(base64_decode($item['path']));
        $entry->filesafe = rawurlencode($item['title']);
        $entry->filesize = - 1;
        $entry->filepath = $item['path'];
        return $entry;
    }

    public function process_directory_fileentry($item) {
        global $COURSE, $CFG;
        $entry = new stdclass();
        $entry->name = $item['title'];
        $entry->filedate = $item['date'];
        $entry->fileurl = $CFG->wwwroot . "/blocks/repo_filemanager/index.php?id=" . $COURSE->id .
        "&noview=1&repoid=" . $this->baserepo->id;
        $entry->filepath = $item['source'];
        $entry->filesafe = rawurlencode($item['title']);
        $entry->filesize = $item['size'];
        return $entry;
    }
}