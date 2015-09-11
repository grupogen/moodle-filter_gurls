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

/**
 * Moodle - Filter get groupurl
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  2015 manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_gurls extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        global $CFG, $USER;
        require_once(dirname(__FILE__) . '/locallib.php');
        if (!is_object($USER)) {
            return false;
        }

        $groupid = filter_gurls_user_group($USER);
        if ($groupid) {
            $assoc = filter_gurls_group_assoc($groupid);
            if ($assoc) {
                $urlmappings = filter_gurls_replacements($assoc);
                if ($urlmappings) {
                    foreach ($urlmappings as $urlmapping) {
                        $text = str_replace($urlmapping->defaulturl, $urlmapping->urlbase, $text);
                    }
                }
            }
        }
        return $text;
    }

}