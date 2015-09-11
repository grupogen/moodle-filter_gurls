<?php
// This file is part of portofolio module for Moodle - http://moodle.org/
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
 * add group assoc form
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

class filter_gurls_defineassoc_add_form extends moodleform {

    public function definition() {
        global $DB;

        $defaultid = $this->_customdata['defaultid'];
        $groupingid = $this->_customdata['groupingid'];

        // For this grouping ID let s get the groups that don t have an associations defined.

        $sql = "SELECT g.id as gid, g.name
           FROM {groupings_groups} gg
           LEFT JOIN {groups} g on (gg.groupid=g.id)
           WHERE groupingid= :groupingid AND
           gg.groupid NOT IN (SELECT groupid FROM {filter_gurls_wassoc} WHERE defaultid= :defaultid)";
        $params = array('groupingid' => $groupingid, 'defaultid' => $defaultid);
        $availablegroups = $DB->get_records_sql($sql, $params);

        $sql2 = "SELECT *
           FROM {filter_gurls_urls} gu
           WHERE defaultid= :defaultid ";
        $params2 = array('defaultid' => $defaultid);
        $availableservers = $DB->get_records_sql($sql2, $params2);

        if ($availablegroups && $availableservers) {
            $groupoptions = array();
            foreach ($availablegroups as $agroup) {
                $groupoptions[$agroup->gid] = $agroup->name;
            }

            $serveroptions = array();
            foreach ($availableservers as $aserver) {
                $serveroptions[$aserver->id] = $aserver->name . ' (' . $aserver->urlbase . ')';
            }

            $mform = & $this->_form;
            $mform->addElement('header', 'newassocaddform', get_string('newassocaddform', 'filter_gurls'));
            $mform->addElement('select', 'groupname', get_string('groupname', 'filter_gurls'), $groupoptions);
            $mform->setType('groupname', PARAM_INT);

            $mform->addElement('select', 'servername', get_string('servername', 'filter_gurls'), $serveroptions);
            $mform->setType('servername', PARAM_INT);

            $buttonarray = array();
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('addnewassoc', 'filter_gurls'));
            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

            $mform->addElement('hidden', 'defaultid', $defaultid);
            $mform->setType('defaultid', PARAM_INT);
            $mform->addElement('hidden', 'groupingid', $groupingid);
            $mform->setType('groupingid', PARAM_INT);

            $mform->closeHeaderBefore('submit');
        } else {
            $mform = & $this->_form;
            $mform->addElement('static', 'description',
                    get_string('infomissing', 'filter_gurls'), get_string('descriptionmissing', 'filter_gurls'));
        }
    }

}