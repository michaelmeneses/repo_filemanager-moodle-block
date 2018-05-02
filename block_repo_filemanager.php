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

class block_repo_filemanager extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_repo_filemanager');
    }
    public function applicable_formats() {
        return array('all' => true);
    }
    public function get_content() {
        global $CFG, $COURSE;
        if (method_exists("context_course", "instance")) {
            $context = context_course::instance($COURSE->id);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        }
        if (!has_capability('moodle/course:managefiles', $context)) {
            $this->content = new stdClass;
            $this->content->footer = '';
            $this->content->text = '';
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = '<a href="' . $CFG->wwwroot . '/blocks/repo_filemanager/index.php?id=' . $COURSE->id . '">' .
            get_string("manage_files", "block_repo_filemanager") . '</a>';
        return $this->content;
    }
}