<?php
// This file is part of CLC report block for Moodle - http://moodle.org/
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
 * Delete default mapping
 *
 * @package    filter
 * @subpackage gurls
 * @copyright  manolescu.dorel@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__) . '/../../config.php');

$defaultid = required_param('defaultid', PARAM_INT);
$groupingid = required_param('groupingid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$defaultobject = $DB->get_record('filter_gurls_default',
        array('groupingid' => $groupingid, 'id' => $defaultid), '*', MUST_EXIST);

require_login();

$syscontext = context_system::instance();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/filter/gurls/deletedefault.php',
        array('groupingid' => $groupingid, 'id' => $defaultid));
$PAGE->set_context($syscontext);

$title = get_string('currentdefault', 'filter_gurls');

// Header and strings.
$PAGE->set_title(format_string($title));
$PAGE->add_body_class('filter_gurls');
$PAGE->set_heading(format_string($title));

// Form processing.
if ($confirm && confirm_sesskey()) {  // The operation was confirmed.
    $DB->delete_records('filter_gurls_default',
            array('id' => $defaultid)) or die('could not remove record'); // D default URL mapping.
    redirect('gurlpanel.php');
}

echo $OUTPUT->header();

// The operation has not been confirmed yet so ask the user to do so.
if ($defaultobject) {
    $strconfirm = get_string('confdefaultdelete', 'filter_gurls') . $defaultobject->defaulturl . ' ?';
}

echo '<br />';
$continue = new moodle_url('/filter/gurls/deletedefault.php',
                array('groupingid' => $groupingid, 'defaultid' => $defaultid,
                    'confirm' => 1));
$cancel = new moodle_url('/filter/gurls/gurlpanel.php', array());
echo $OUTPUT->confirm("<p>$strconfirm</p>", $continue, $cancel);

echo $OUTPUT->footer();