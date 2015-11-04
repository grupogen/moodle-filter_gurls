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
 * Add/remove group from grouping.
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core_group
 */
require(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');

$groupingid = required_param('groupingid', PARAM_INT);
$defurlid = required_param('defurlid', PARAM_INT); // Url id.
$defaultid = required_param('defaultid', PARAM_INT); // Default id.

$PAGE->set_url($CFG->wwwroot . '/admin/settings.php?section=filtersettinggurls');
$PAGE->set_pagelayout('standard');

if (!$grouping = $DB->get_record('groupings', array('id' => $groupingid))) {
    print_error('invalidgroupid');
}

if (!$course = $DB->get_record('course', array('id' => SITEID))) {
    print_error('invalidcourse');
}

if (!$url = $DB->get_record('filter_gurls_urls', array('id' => $defurlid))) {
    print_error('invalidurl');
}

$courseid = $course->id;

require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:managegroups', $context);

$returnurl = $CFG->wwwroot . '/filter/gurls/defineurl.php?action=defineurl&defaultid=' . $defaultid;


if ($frm = data_submitted()) {

    if (isset($frm->cancel)) {
        redirect($returnurl);
    } else if (isset($frm->add) and !empty($frm->addselect)) {
        foreach ($frm->addselect as $groupid) {
            // Ask this method not to purge the cache, we'll do it ourselves afterwards.
            filter_gurls_assign_group((int) $groupid, $defurlid, $defaultid);
        }
        // Invalidate the course groups cache seeing as we've changed it.
        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($courseid));
    } else if (isset($frm->remove) and !empty($frm->removeselect) && confirm_sesskey()) {
        foreach ($frm->removeselect as $groupid) {
            // Ask this method not to purge the cache, we'll do it ourselves afterwards.
            filter_gurls_unassign_group((int) $groupid, $defurlid, $defaultid);
        }
        // Invalidate the course groups cache seeing as we've changed it.
        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($courseid));
    }
}


$currentmembers = array();
$potentialmembers = array();

if ($groups = filter_gurls_groupinggroups($grouping->id)) {
    if ($assignment = $DB->get_records('filter_gurls_wassoc', array('defaultid' => $defaultid))) {
        foreach ($assignment as $ass) {
            if ($ass->urlid == $defurlid) {
                $currentmembers[$ass->groupid] = $groups[$ass->groupid];
            }
            unset($groups[$ass->groupid]);
        }
    }
    $potentialmembers = $groups;
}

$currentmembersoptions = '';
$currentmemberscount = 0;
if ($currentmembers) {
    foreach ($currentmembers as $group) {
        $currentmembersoptions .= '<option value="' . $group->id . '.">' . format_string($group->name) . '</option>';
        $currentmemberscount++;
    }

    // Get course managers so they can be highlighted in the list.
    if ($managerroles = get_config('', 'coursecontact')) {
        $coursecontactroles = explode(',', $managerroles);
        foreach ($coursecontactroles as $roleid) {
            $role = $DB->get_record('role', array('id' => $roleid));
            $managers = get_role_users($roleid, $context, true, 'u.id', 'u.id ASC');
        }
    }
} else {
    $currentmembersoptions .= '<option>&nbsp;</option>';
}

$potentialmembersoptions = '';
$potentialmemberscount = 0;
if ($potentialmembers) {
    foreach ($potentialmembers as $group) {
        $potentialmembersoptions .= '<option value="' . $group->id . '.">' . format_string($group->name) . '</option>';
        $potentialmemberscount++;
    }
} else {
    $potentialmembersoptions .= '<option>&nbsp;</option>';
}

// Print the page and form.
$strgroups = get_string('groups');
$strparticipants = get_string('participants');
$straddgroupstogroupings = get_string('addgroupstogroupings', 'group');

$groupingname = format_string($grouping->name);

$navurl = new moodle_url('/filter/gurls/defineurl.php',
        array('defaultid' => $defaultid, 'groupingid' => $groupingid));
$PAGE->navbar->add(get_string('defineurl', 'filter_gurls'), $navurl);
$PAGE->navbar->add(get_string('defineassoc', 'filter_gurls'));

$PAGE->requires->js(new moodle_url('http://cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js'));
$PAGE->requires->js(new moodle_url('http://cdnjs.cloudflare.com/ajax/libs/jinplace/1.2.1/jinplace.min.js'));
$PAGE->requires->js('/filter/gurls/gurls.js');

echo $OUTPUT->header();
echo html_writer::tag('h3', 'GURLS', array());
echo html_writer::tag('hr', '', array());

if ($url) {
    $editurl = new moodle_url('editurl.php', array('groupingid' => $groupingid, 'defaultid' => $defaultid,
        'defurlid' => $defurlid));
    $action = $OUTPUT->action_icon($editurl, new pix_icon('i/edit', get_string('editdefaulturl', 'filter_gurls')));
    echo html_writer::tag('span', get_string('mapname', 'filter_gurls') . ' : ', array());
    echo html_writer::tag('span', $url->name, array('class' => 'editable',
        'data-url' => 'editnameinline.php?groupingid=' . $groupingid . '&defaultid=' .
        $defaultid . '&defurlid=' . $defurlid,
        'data-activator' => '#edit-activator2',
        'data-input-class' => 'short'));
    echo html_writer::tag('span', $action, array('class' => 'button', 'id' => 'edit-activator2'));
    echo html_writer::tag('p', '', array());

    echo html_writer::tag('span', get_string('urlbase', 'filter_gurls') . ' : ', array());
    echo html_writer::tag('span', $url->urlbase, array('class' => 'editable',
        'data-url' => 'editurlinline.php?groupingid=' . $groupingid . '&defaultid=' .
        $defaultid . '&defurlid=' . $defurlid ,
        'data-activator' => '#edit-activator',
        'data-input-class' => 'short'));
    echo html_writer::tag('span', $action, array('class' => 'button', 'id' => 'edit-activator'));
    echo html_writer::tag('br', '', array());
}
?>
<div id="addmembersform">
    <h3 class="main"></h3>
    <form id="assignform" method="post" action="">
        <div>
            <table summary="" class="generaltable generalbox groupmanagementtable boxaligncenter">
                <tr>
                    <td id="existingcell">
                        <label for="removeselect"><?php print_string('existingmembers', 'group', $currentmemberscount); ?></label>
                        <div class="userselector" id="removeselect_wrapper">
                            <select name="removeselect[]" size="20" id="removeselect" multiple="multiple"
                                    onfocus="document.getElementById('assignform').add.disabled = true;
                                        document.getElementById('assignform').remove.disabled = false;
                                        document.getElementById('assignform').addselect.selectedIndex = -1;">
                                    <?php echo $currentmembersoptions ?>
                            </select></div></td>
                    <td id="buttonscell">
                        <p class="arrow_button">
                            <input name="add" id="add" type="submit"
                                   value="<?php echo $OUTPUT->larrow() . '&nbsp;' . get_string('add'); ?>"
                                   title="<?php print_string('add'); ?>" /><br>
                            <input name="remove" id="remove" type="submit"
                                   value="<?php echo get_string('remove') . '&nbsp;' . $OUTPUT->rarrow(); ?>"
                                   title="<?php print_string('remove'); ?>" />
                        </p>
                    </td>
                    <td id="potentialcell">
                        <label for="addselect"><?php print_string('potentialmembers', 'group', $potentialmemberscount); ?></label>
                        <div class="userselector" id="addselect_wrapper">
                            <select name="addselect[]" size="20" id="addselect" multiple="multiple"
                                    onfocus="document.getElementById('assignform').add.disabled = false;
                                        document.getElementById('assignform').remove.disabled = true;
                                        document.getElementById('assignform').removeselect.selectedIndex = -1;">
                                    <?php echo $potentialmembersoptions ?>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr><td colspan="3" id="backcell">
                        <input type="submit" name="cancel" value="<?php print_string('backtourl', 'filter_gurls'); ?>" />
                    </td></tr>
            </table>
        </div>
    </form>
</div>

<?php
echo $OUTPUT->footer();