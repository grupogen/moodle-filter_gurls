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
 * edit default mapping form
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');

class filter_gurls_mapping_edit_form extends moodleform {

    public function definition() {
        global $DB;

        $groupingid = $this->_customdata['groupingid'];
        $defaultid = $this->_customdata['defaultid'];

        $defaulturl = $DB->get_record('filter_gurls_default',
                array('groupingid' => $groupingid, 'id' => $defaultid), '*', MUST_EXIST);

        $mform = & $this->_form;
        $mform->addElement('header', 'defaulturladdform', get_string('defaulturladdform', 'filter_gurls'));
        $mform->addElement('text', 'defaulturl', get_string('edefaulturl', 'filter_gurls'), array('size' => '60'));
        $mform->setType('defaulturl', PARAM_URL);
        $mform->setDefault('defaulturl', $defaulturl->defaulturl);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('editdefaulturl', 'filter_gurls'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        $mform->addElement('hidden', 'defaultid', $defaultid);
        $mform->setType('defaultid', PARAM_INT);
        $mform->addElement('hidden', 'groupingid', $groupingid);
        $mform->setType('groupingid', PARAM_INT);
    }
}