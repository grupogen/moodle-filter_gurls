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
 * edit group association
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__) . '/../../config.php');
require_once('editassoc_form.php');

$defassocid = required_param('waid', PARAM_INT); // Url id.
$groupingid = required_param('groupingid', PARAM_INT); // Grouping id.
$defaultid = required_param('defaultid', PARAM_INT); // Default id.

require_login();

$PAGE->set_context(context_system::instance());
require_capability('moodle/site:config', context_system::instance());
$navurl = new moodle_url('/filter/gurls/mapgroups.php',
                array('defaultid' => $defaultid,
                    'groupingid' => $groupingid));

$PAGE->set_url('/filter/gurls/editassoc.php', array('groupingid' => $groupingid,
    'defassocid' => $defassocid));
$PAGE->navbar->add(get_string('defineurl', 'filter_gurls'), $navurl);
$PAGE->navbar->add(get_string('editassoc', 'filter_gurls'));

$mform = new filter_gurls_assoc_edit_form(null,
        array('groupingid' => $groupingid, 'defassocid' => $defassocid, 'defaultid' => $defaultid));

if ($mform->is_cancelled()) {
    redirect($navurl);
} else if ($newassoc = $mform->get_data()) {
    global $DB;
    $assoc = new stdClass();
    $assoc->id = $defassocid;
    $assoc->defaultid = $defaultid;
    $assoc->urlid = $newassoc->servername;
    $assoc->timemodified = time();
    $DB->update_record('filter_gurls_wassoc', $assoc);
    redirect($navurl, get_string('assocedited', 'filter_gurls'));
} else {
    $PAGE->set_title(format_string(get_string('definedassoc', 'filter_gurls')));
    $PAGE->add_body_class('filter_gurls');
    $PAGE->set_heading(format_string(get_string('editassoc', 'filter_gurls')));
    echo $OUTPUT->header();
    $mform->display();
}
echo '<br />';
echo $OUTPUT->footer();