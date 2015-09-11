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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
$defaultid = required_param('defaultid', PARAM_INT); // Default id.
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', context_system::instance());
$navurl = new moodle_url($CFG->wwwroot . '/admin/settings.php?section=filtersettinggurls', array());

$navurl2 = new moodle_url('/filter/gurls/gurlpanel.php', array());
$currenturl = new moodle_url('/filter/gurls/mapgroups.php', array('defaultid' => $defaultid));

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_heading(get_string('mapgroups', 'filter_gurls'));
$PAGE->set_title(get_string('mapgroups', 'filter_gurls'));
$PAGE->navbar->add(get_string('filtersettings', 'filter_gurls'), $navurl);
$PAGE->navbar->add(get_string('gurlpanel', 'filter_gurls'), $navurl2);
$PAGE->navbar->add(get_string('mapgroups', 'filter_gurls'));

$output = $PAGE->get_renderer('filter_gurls');

echo $OUTPUT->header();

$output->filter_gurl_tabs('mapgroups', $defaultid);
echo $OUTPUT->footer();