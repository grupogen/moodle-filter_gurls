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

class filter_gurls_url_edit_form extends moodleform {

    public function definition() {
        global $DB;

        $defurlid = $this->_customdata['defurlid'];
        $defaultid = $this->_customdata['defaultid'];
        $groupingid = $this->_customdata['groupingid'];

        $definedurl = $DB->get_record('filter_gurls_urls', array('id' => $defurlid), '*', MUST_EXIST);

        $mform = & $this->_form;
        $mform->addElement('header', 'editurlform', get_string('editurlform', 'filter_gurls'));
        $mform->addElement('text', 'urlname', get_string('urlname', 'filter_gurls'), array('size' => '60'));
        $mform->setType('urlname', PARAM_ALPHANUMEXT);
        $mform->setDefault('urlname', $definedurl->name);
        $mform->addElement('text', 'urlbase', get_string('urlbase', 'filter_gurls'), array('size' => '60'));
        $mform->setType('urlbase', PARAM_URL);
        $mform->setDefault('urlbase', $definedurl->urlbase);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('editurl', 'filter_gurls'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        $mform->addElement('hidden', 'defaultid', $defaultid);
        $mform->setType('defaultid', PARAM_INT);
        $mform->addElement('hidden', 'groupingid', $groupingid);
        $mform->setType('groupingid', PARAM_INT);

        $mform->addElement('hidden', 'defurlid', $defurlid);
        $mform->setType('defurlid', PARAM_INT);

        $mform->closeHeaderBefore('submit');
    }

}