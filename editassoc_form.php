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
 * edit defined URL form
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

class filter_gurls_assoc_edit_form extends moodleform {

    public function definition() {
        global $DB;

        $defassocid = $this->_customdata['defassocid'];
        $defaultid = $this->_customdata['defaultid'];
        $groupingid = $this->_customdata['groupingid'];

        $defassoc = $DB->get_record('filter_gurls_wassoc',
                array('id' => $defassocid), '*', MUST_EXIST);
        if ($defassoc) {
            $group = $DB->get_record('groups',
                    array('id' => $defassoc->groupid), '*', MUST_EXIST);
            $sql2 = "SELECT *
                    FROM {filter_gurls_urls} gu
                    WHERE defaultid= :defaultid ";
            $params2 = array('defaultid' => $defaultid);
            $availableservers = $DB->get_records_sql($sql2, $params2);

            $serveroptions = array();
            foreach ($availableservers as $aserver) {
                $serveroptions[$aserver->id] = $aserver->name . ' (' . $aserver->urlbase . ')';
            }

            if ($serveroptions) {
                $mform = & $this->_form;
                $mform->addElement('header', 'editassocform', get_string('editassocform', 'filter_gurls'));
                $mform->addElement('static', 'groupname', get_string('groupname', 'filter_gurls'), $group->name);

                $mform->addElement('select', 'servername', get_string('servername', 'filter_gurls'), $serveroptions);
                $mform->setType('servername', PARAM_INT);

                $buttonarray = array();
                $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('editassoc', 'filter_gurls'));
                $buttonarray[] = &$mform->createElement('cancel');
                $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

                $mform->addElement('hidden', 'defaultid', $defaultid);
                $mform->setType('defaultid', PARAM_INT);
                $mform->addElement('hidden', 'groupingid', $groupingid);
                $mform->setType('groupingid', PARAM_INT);

                $mform->addElement('hidden', 'waid', $defassocid);
                $mform->setType('waid', PARAM_INT);

                $mform->closeHeaderBefore('submit');
            }
        }
    }
}