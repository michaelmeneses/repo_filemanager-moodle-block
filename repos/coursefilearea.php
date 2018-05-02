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

require_once("block_repofile_type.php");

/**
 * Management functions class for the coursefile area repo type
 * @author Tim Williams (tmw@autotrain.org) for EA LLP 2010
 * @licence GPL v3
 *
 */
class block_repofile_coursefilearea extends block_repofile_type {
    private $course;
    private $basedir;

    public function __construct($c) {
        global $DB, $CFG;
        $this->course = $c;
        if (method_exists("context_course", "instance")) {
            $context = context_course::instance($this->course->id);
        } else {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
        }
        require_capability('moodle/course:managefiles', $context);
        $this->basedir = make_upload_directory($this->course->id) . '/';
        if (!$this->basedir) {
            error("The site administrator needs to fix the file permissions");
        }
    }

    /**
     * Returns true if the repository type can :
     * - Move files
     * - Delete files
     * - Make new directories
     * - Accept file uploads
     *
     */
    public function can_manage_files() {
        return true;
    }

    /**
     * Returns true if the repository can provide zip/unzip capabilities
     *
     */
    public function can_zip() {
        return true;
    }

    /**
     * Returns true if the repository allows text content to be edited
     *
     */
    public function can_edit() {
        return true;
    }

    public function get_all_paths($filelist, $level = 0) {
        $result = array();
        foreach ($filelist as $file) {
            if (is_dir($this->basedir . $file)) {
                $item = new stdclass();
                $item->path = $file;
                $item->dir = true;
                $item->level = $level;
                $result[] = $item;
                $subfilelist = array();
                $currdir = opendir($this->basedir . $file);
                while (false !== ($subfile = readdir($currdir))) {
                    if ($subfile <> ".." && $subfile <> ".") {
                        $subfilelist[] = $file . "/" . $subfile;
                    }
                }
                $result = array_merge($result, $this->get_all_paths($subfilelist, $level + 1));
            } else {
                $item = new stdclass();
                $item->path = $file;
                $item->dir = false;
                $item->level = $level;
                $result[] = $item;
            }
        }
        return $result;
    }

    public function get_parent_link($wdir) {
        $dn = dirname($wdir);
        if ($dn != ".") {
            $fileurl = $this->check_url($dn);
        } else {
            $fileurl = "";
        }
        return $fileurl;
    }

    public function get_nav_links($wdir, $choose) {
        global $COURSE;
        $navlinks = array();
        $strfiles = get_string("files");
        if ($wdir == "") {
            $navlinks[] = array('name' => $strfiles, 'link' => null, 'type' => 'misc');
        } else {
            $dirs = explode("/", $wdir);
            $numdirs = count($dirs);
            $link = "";
            $navlinks[] = array('name' => $strfiles, 'link' => "?id=$COURSE->id&amp;wdir=/&amp;choose=$choose", 'type' => 'misc');
            for ($i = 1; $i < $numdirs - 1; $i++) {
                $link .= "/" . urlencode($dirs[$i]);
                $navlinks[] = array('name' => $dirs[$i],
                    'link' => "?id=$COURSE->id&amp;wdir=$link&amp;choose=$choose",
                    'type' => 'misc');
            }
            $navlinks[] = array('name' => $dirs[$numdirs - 1], 'link' => null, 'type' => 'misc');
        }
        return $navlinks;
    }

    public function get_directory_listing($wdir) {
        global $COURSE, $CFG;
        if (!is_dir($this->basedir . $wdir)) {
            print_error("Requested directory does not exist.", "$CFG->wwwroot/files/index.php?id=$id");
            return array();
        }
        $data = new stdclass();
        $data->dirlist = array();
        $data->filelist = array();
        $fullpath = $this->basedir . $wdir;
        $directory = opendir($fullpath);
        while (false !== ($file = readdir($directory))) {
            if ($file == "." || $file == "..") {
                continue;
            }
            $entry = new stdclass();
            $entry->name = $file;
            $entry->filedate = filemtime("$fullpath/$file");
            if ($wdir == "") {
                $entry->filepath = $this->check_url($file);
            } else {
                $entry->filepath = $this->check_url($wdir) . "/" . $this->check_url($file);
            }
            if (is_dir($fullpath . "/" . $file)) {
                $entry->filesafe = $this->check_url($file);
                $entry->filesize = get_directory_size("$fullpath/$file");
                $data->dirlist[] = $entry;
            } else {
                if ($CFG->slasharguments) {
                    $urlst = $CFG->wwwroot . "/repository/coursefilearea/file.php/";
                } else {
                    $urlst = $CFG->wwwroot . "/repository/coursefilearea/file.php?file=/";
                }
                if ($wdir == "") {
                    $entry->fileurl = $urlst . $COURSE->id . "/" . $this->check_url($wdir) . $this->check_url($file);
                } else {
                    $entry->fileurl = $urlst . $COURSE->id . "/" . $this->check_url($wdir) . "/" . $this->check_url($file);
                }
                $entry->filesafe = $this->check_url($file);
                $entry->filesize = filesize("$fullpath/$file");
                $data->filelist[] = $entry;
            }
        }
        closedir($directory);
        return $data;
    }

    public function check_url($u) {
        if (!strpos($u, "/")) {
            return rawurlencode($u);
        }
        $all = explode("/", $u);
        $f = "";
        foreach ($all as $e) {
            $f = $f . rawurlencode($e) . "/";
        }
        return substr($f, 0, strlen($f) - 1);
    }

    public function delete_files($filelist) {
        $message = "";
        foreach ($filelist as $file) {
            $fullfile = $this->basedir . $file;
            if (!fulldelete($fullfile)) {
                $message .= "<br />" . get_string('deleteerror', 'block_repo_filemanager') . ": $fullfile";
            }
        }
        return $message;
    }

    public function move_files($filelist, $dest) {
        $message = "";
        foreach ($filelist as $file) {
            $shortfile = basename($file);
            $oldfile = $this->basedir . $file;
            $newfile = $this->basedir . $dest . "/" . $shortfile;
            if (!rename($oldfile, $newfile)) {
                $message .= "<p class='error'>" . get_string('error') . ": $shortfile " .
                get_string('notmoved', 'block_repo_filemanager') . "</p>";
            }
        }
        return $message;
    }

    public function rename_file($oldname, $newname, $dir) {
        global $CFG;
        $newname = str_replace(" ", "_", $this->basedir . clean_param($dir . "/" . $newname, PARAM_PATH));
        $oldname = $this->basedir . $dir . "/" . $oldname;
        if (file_exists($newname)) {
            return "<p class='error'>" . get_string('error') . ": $name "
                . get_string('alreadyexists', 'block_repo_filemanager') . "!</p>";
        } else {
            if (!rename($oldname, $newname)) {
                return "<p class='error'>" . get_string('error') . ": "
                    . get_string('renameerror', 'block_repo_filemanager') . " $oldname " . get_string('to') . " $name</p>";
            }
            return "";
        }
    }

    public function mkdir($folder, $name) {
        global $CFG;
        $name = str_replace(" ", "_", clean_filename($name));
        $path = "$this->basedir$folder/$name";
        if (file_exists($path)) {
            return "<p class='error'>" . get_string('error') . ": $name " .
            get_string('alreadyexists', 'block_repo_filemanager') . "!</p>";
        } else {
            if (!mkdir($path, $CFG->directorypermissions)) {
                echo "<p class='error'>" . get_string('nocreate', 'block_repo_filemanager') . " $name</p>";
            }
        }
        return "";
    }

    public function upload($destdir) {
        global $CFG, $COURSE;
        $this->course->maxbytes = 0; // We are ignoring course limits.
        require_once($CFG->dirroot . '/blocks/repo_filemanager/uploadlib.php');
        $um = new block_repo_filemanger_upload_manager('userfile', false, false, $COURSE, false, 0);

        if ($um->process_file_uploads($this->basedir . $destdir)) {
            $nospace = str_replace(" ", "_", clean_filename($um->get_new_filename()));
            rename($this->basedir . $destdir . "/" . $um->get_new_filename(), $this->basedir . $destdir . "/" . $nospace);
            return get_string('uploadedfile');
        }
        return "";
    }

    public function max_upload_bytes() {
        global $CFG;
        return $CFG->maxbytes;
    }

    public function save_text($file, $text) {
        $fileptr = fopen($this->basedir . '/' . $file, "w");
        $text = preg_replace('/\x0D/', '', $text); // More Info: http://moodle.org/mod/forum/discuss.php?d=38860.
        fputs($fileptr, stripslashes($text));
        fclose($fileptr);
    }

    public function get_text($file) {
        $fileptr = fopen($this->basedir . '/' . $file, "r");
        $contents = fread($fileptr, filesize($this->basedir . '/' . $file));
        fclose($fileptr);
        return $contents;
    }

    public function zip_files($filelist, $zipfile) {
        $name = clean_filename($zipfile);
        $files = array();
        foreach ($filelist as $file) {
            $files[] = "$this->basedir/$file";
        }

        $packer = get_file_packer('application/zip');

        if (!$this->zip_files_main($files, $this->basedir . $zipfile)) {
            return get_string("zipfileserror", "error");
        }

        return "";
    }

    function zip_files_main($originalfiles, $destination) {

        // Extract everything from destination.
        $path_parts = pathinfo(cleardoubleslashes($destination));
        $destpath = $path_parts["dirname"];       //The path of the zip file
        $destfilename = $path_parts["basename"];  //The name of the zip file
        $extension = $path_parts["extension"];    //The extension of the file

        // If no file, error.
        if (empty($destfilename)) {
            return false;
        }

        // If no extension, add it.
        if (empty($extension)) {
            $extension = 'zip';
            $destfilename = $destfilename.'.'.$extension;
        }

        // Check destination path exists.
        if (!is_dir($destpath)) {
            return false;
        }

        // Check destination path is writable. TODO!!

        // Clean destination filename.
        $destfilename = clean_filename($destfilename);

        // Now check and prepare every file.
        $files = array();
        $origpath = NULL;

        foreach ($originalfiles as $file) {  // Iterate over each file.
            // Check for every file.
            $tempfile = cleardoubleslashes($file); // no doubleslashes!
            // Calculate the base path for all files if it isn't set.
            if ($origpath === NULL) {
                $origpath = rtrim(cleardoubleslashes(dirname($tempfile)), "/");
            }
            // See if the file is readable.
            if (!is_readable($tempfile)) {  // Is readable.
                continue;
            }
            // See if the file/dir is in the same directory than the rest.
            if (rtrim(cleardoubleslashes(dirname($tempfile)), "/") != $origpath) {
                continue;
            }
            // Add the file to the array.
            $files[] = $tempfile;
        }

        $zipfiles = array();
        $start = strlen($origpath)+1;
        foreach($files as $file) {
            $zipfiles[substr($file, $start)] = $file;
        }

        $packer = get_file_packer('application/zip');

        return $packer->archive_to_pathname($zipfiles, $destpath . '/' . $destfilename);
    }

    public function unzip_file($zipfile) {
        if (!$this->unzip_file_main($this->basedir . $zipfile)) {
            return get_string("unzipfileserror", "error");
        }

        return "";
    }

    function unzip_file_main($zipfile, $destination = '', $showstatus_ignored = true) {
        // Extract everything from zipfile.
        $path_parts = pathinfo(cleardoubleslashes($zipfile));
        $zippath = $path_parts["dirname"];       //The path of the zip file
        $zipfilename = $path_parts["basename"];  //The name of the zip file
        $extension = $path_parts["extension"];    //The extension of the file

        // If no file, error.
        if (empty($zipfilename)) {
            return false;
        }

        // If no extension, error.
        if (empty($extension)) {
            return false;
        }

        // Clear $zipfile.
        $zipfile = cleardoubleslashes($zipfile);

        // Check zipfile exists.
        if (!file_exists($zipfile)) {
            return false;
        }

        // If no destination, passed let's go with the same directory.
        if (empty($destination)) {
            $destination = $zippath;
        }

        // Clear $destination.
        $destpath = rtrim(cleardoubleslashes($destination), "/");

        // Check destination path exists.
        if (!is_dir($destpath)) {
            return false;
        }

        $packer = get_file_packer('application/zip');

        $result = $packer->extract_to_pathname($zipfile, $destpath);

        if ($result === false) {
            return false;
        }

        foreach ($result as $status) {
            if ($status !== true) {
                return false;
            }
        }

        return true;
    }


    public function list_zip($zipfile) {
        global $CFG;
        include_once("$CFG->libdir/filestorage/zip_packer.php");
        $zp = new zip_packer();
        return $zp->list_files($this->basedir . $zipfile);
    }

    public function is_browsable() {
        return true;
    }
}