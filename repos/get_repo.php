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

function get_repomanager_instance($repoi, $course) {
    switch ($repoi->get_meta()->type) {
        case "coursefilearea":
            require_once("coursefilearea.php");
            return new block_repofile_coursefilearea($course);
        case "local":
        case "recent":
        case "user":
            require_once("generic.php");
            return new block_repofile_generic($repoi);
        case "filesystem":
            require_once("filesystem.php");
            return new block_repofile_filesystem($repoi);
    }
    require_once('block_repofile_type.php');
    return new block_repofile_type();
}
