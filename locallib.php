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
 * Filter gurl general functions
 *
 * @package    filter_gurl
 * @copyright  2015 manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Returns int if user is memeber of a front page group
 *
 * @param Object $user
 * @return int : user group id for front page .
 */
function filter_gurls_user_group($user) {
    global $DB;

    $sql2 = "SELECT gm.id as gid, gm.*
            FROM {groups_members} gm
            LEFT JOIN {groups} g ON (gm.groupid=g.id)
            WHERE  g.courseid= :courseid and gm.userid= :userid
            ";
    $params2 = array('userid' => $user->id, 'courseid' => SITEID);
    $memberships = $DB->get_records_sql($sql2, $params2); // Front page groupings.

    if ($memberships) {
        $maingroup = 0;
        foreach ($memberships as $mb) {
            $maingroup = $mb->groupid;
        }
        return $maingroup;
    }
    return 0;
}

/**
 * Returns object if the group has associations
 *
 * @param int : groupid
 * @return obj: group associations
 */
function filter_gurls_group_assoc($groupid) {
    global $DB;
    $sql = "SELECT *
            FROM {filter_gurls_wassoc} gw
            WHERE  gw.groupid= :groupid
            ";

    $params = array('groupid' => $groupid);
    $groupassoc = $DB->get_records_sql($sql, $params); // Front page groupings.
    if ($groupassoc) {
        return $groupassoc;
    }

    return 0;
}

/**
 * Returns replacements
 *
 * @param obj : group associations
 * @return obj: url to be replaced
 */
function filter_gurls_replacements($groupassoc) {
    global $DB;
    $assocarray = '';
    foreach ($groupassoc as $assoc) {
        $assocarray .= $assoc->id . ',';
    }
    $assocarray = trim($assocarray, ",");

    $sql = "SELECT *
            FROM {filter_gurls_wassoc} gw
            LEFT JOIN {filter_gurls_default} gd ON (gw.defaultid = gd.id)
            LEFT JOIN {filter_gurls_urls} gu ON (gw.urlid = gu.id)
            WHERE  gu.defaultid=gd.id AND gw.id IN ($assocarray)
            ";

    $params = array();
    $mappings = $DB->get_records_sql($sql, $params); // Front page groupings.
    if ($mappings) {
        return $mappings;
    }

    return 0;
}

function filter_gurls_groupinggroups($groupingid) {
    global $DB;
    $sql = "SELECT g.*
         FROM {groups} g
         LEFT JOIN {groupings_groups} gg ON (g.id=gg.groupid)
         WHERE  g.courseid= :courseid AND gg.groupingid= :groupingid
         ";
    $params = array('groupingid' => $groupingid, 'courseid' => SITEID);
    $groupinggroups = $DB->get_records_sql($sql, $params); // Groups in a particular grouping.

    if ($groupinggroups) {
        return $groupinggroups;
    }
    return 0;
}

/**
 * Add group association from group UI manager
 *
 * @param int : groupid
 * @param int:  defurlid
 * @param int:  defaultid
 * @return nothing
 */
function filter_gurls_assign_group($groupid, $defurlid, $defaultid) {
    global $DB;

    $newassoc = new stdClass();
    $newassoc->defaultid = $defaultid;
    $newassoc->groupid = $groupid;
    $newassoc->urlid = $defurlid;
    $newassoc->timemodified = time();

    $DB->insert_record('filter_gurls_wassoc', $newassoc);
}

/**
 * Remove group association from group UI manager
 *
 * @param int : groupid
 * @param int:  defurlid
 * @param int:  defaultid
 * @return nothing
 */
function filter_gurls_unassign_group($groupid, $defurlid, $defaultid) {
    global $DB;

    $DB->delete_records('filter_gurls_wassoc', array('urlid' => $defurlid,
                'defaultid' => $defaultid, 'groupid' => $groupid)) or die('could not remove record');
}