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


require_once($CFG->libdir . '/tablelib.php');

/**
 * Standard HTML output renderer for badges
 */
class filter_gurls_renderer extends plugin_renderer_base {

    // Prints tabs for gurl editing.
    public function filter_gurl_tabs($current = 'defineurl', $defaultid) {
        global $DB;
        $context = context_system::instance();
        $row = array();

        if (has_capability('moodle/site:config', $context)) {
            $row[] = new tabobject('defineurl',
                    new moodle_url('/filter/gurls/defineurl.php',
                            array('action' => 'defineurl', 'defaultid' => $defaultid)), get_string('defineurl', 'filter_gurls')
            );
        }

        if (has_capability('moodle/site:config', $context)) {
            $row[] = new tabobject('mapgroups',
                    new moodle_url('/filter/gurls/mapgroups.php',
                            array('action' => 'mapgroups', 'defaultid' => $defaultid)), get_string('mapgroups', 'filter_gurls')
            );
        }
        echo $this->tabtree($row, $current);
    }

    public function filter_gurl_defaulttable() {
        global $DB, $CFG, $OUTPUT, $USER;
        $updatepref = optional_param('updatepref', 0, PARAM_BOOL);

        if ($updatepref) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage;
            $filter = optional_param('filter', 0, PARAM_INT);
            set_user_preference('gurls_perpage', $perpage);
            set_user_preference('gurls_filter', $filter);
        }

        $perpage = get_user_preferences('gurls_perpage', 10);
        $filter = get_user_preferences('gurls_filter', 0);
        $page = optional_param('page', 0, PARAM_INT);
        echo html_writer::tag('h3', 'GURLS', array());
        echo html_writer::tag('hr', '', array());
        echo html_writer::tag('p', get_string('help_text', 'filter_gurls'), array());
        echo html_writer::tag('br', '', array());

        $sqlrep = "SELECT gp.id, gp.name, gp.description, fg.defaulturl
           FROM {groupings} gp
           LEFT JOIN {filter_gurls_default} fg ON (gp.id=fg.groupingid)
           WHERE gp.courseid = :courseid ";
        $paramsrep = array('courseid' => SITEID);
        $allgurls = $DB->get_records_sql($sqlrep, $paramsrep);

        $tablecolumns = array('name', 'urlswap', 'actions', 'urlmappings');
        $tableheaders = array(
            get_string('name', 'filter_gurls'),
            get_string('urlswap', 'filter_gurls'),
            get_string('urlmappings', 'filter_gurls'),
            get_string('actions', 'filter_gurls')
        );

        $table = new flexible_table('filter-gurls-groupings');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot . '/filter/gurls/gurlpanel.php');

        $table->sortable(true, 'grouping'); // Sorted by reportname by default.
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_class('name', 'name');
        $table->column_class('urlswap', 'urlswap');
        $table->column_class('description', 'description');
        $table->column_class('urlmappings', 'urlmappings');
        $table->column_class('actions', 'actions');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'mappings');
        $table->set_attribute('width', '100%');

        $table->no_sorting('urlswap');
        $table->no_sorting('description');
        $table->no_sorting('actions');
        $table->no_sorting('urlmappings');
        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();
        // Construct the SQL..

        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if (isset($allgurls)) {
            $where .= ' WHERE gp.courseid=' . SITEID . ' ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $sort;
        }

        if (!empty($allgurls)) {
            $select = "SELECT gp.id, gp.name, gp.description, fg.defaulturl  ";
            $sql = 'FROM {groupings} gp
            LEFT JOIN {filter_gurls_default} fg ON (gp.id=fg.groupingid) ';

            $sgurls = $DB->get_records_sql($select . $sql . $where . $sort, $params,
                    $table->get_page_start(), $table->get_page_size());
            $table->pagesize($perpage, count($sgurls));
            $offset = $page * $perpage;
            $rowclass = null;

            $endposition = $offset + $perpage;
            $currentposition = 0;

            foreach ($sgurls as $sgurl) {
                if ($currentposition == $offset && $offset < $endposition) {
                    $actions = '';
                    $maptext = '';
                    $grouptext = '';
                    $deletestatus = 1;

                    if ($mappings = $this->filter_gurl_getdefinedurls($sgurl->id)) {
                        foreach ($mappings as $mapping) {
                            $assgroups = $this->filter_gurl_getmapgroups($mapping->gid);
                            // Get the groups associated with a particular URL.
                            $linkul = new moodle_url($mapping->urlbase, array());
                            $action = new popup_action('click', $linkul, 'popup', array('width' => 800, 'height' => 600));
                            $maptext .= '<span style="color:#339933">' . $mapping->name . '</span>' . ' (' .
                                    $OUTPUT->action_link($linkul,
                                            $mapping->urlbase, $action, array('title' => 'popup')) . ')' . $assgroups . '<br/>';
                        }
                        $deletestatus = 0;
                    }

                    if (!$deafulturl = $DB->get_record('filter_gurls_default', array('groupingid' => $sgurl->id))) {
                        $addurl = new moodle_url('addmapping.php', array('groupingid' => $sgurl->id, 'sesskey' => $USER->sesskey));
                        $actions = $OUTPUT->single_button($addurl, get_string('adddefaulturl', 'filter_gurls'), 'GET');
                    } else {
                        $actions .= ' ';
                        $defineurl = new moodle_url('defineurl.php',
                                array('groupingid' => $sgurl->id, 'defaultid' => $deafulturl->id, 'sesskey' => $USER->sesskey));
                        $actions .= $OUTPUT->action_icon($defineurl,
                                new pix_icon('i/edit', get_string('defineurl', 'filter_gurls')));
                        if ($deletestatus) {
                            $deleteurl = new moodle_url('deletedefault.php',
                                    array('groupingid' => $sgurl->id, 'defaultid' => $deafulturl->id, 'sesskey' => $USER->sesskey));
                            $actions .= $OUTPUT->action_icon($deleteurl,
                                    new pix_icon('t/delete', get_string('deletedefaulturl', 'filter_gurls')));
                        }
                    }

                    if ($grupuri = $this->filter_gurl_getgroups($sgurl->id)) {
                        foreach ($grupuri as $grup) {
                            $grouptext .= $grup->name . ', ';
                        }
                    }
                    $row = array($sgurl->name, $sgurl->defaulturl, $maptext, $actions);
                    $offset++;
                    $table->add_data($row, $rowclass);
                }
                $currentposition++;
            }
            $table->totalrows = count($allgurls);
            echo $table->print_html();
        } else {
            $link = new stdClass();
            $link->url = new moodle_url($CFG->wwwroot . '/group/groupings.php?id=1', array());
            // Required, but you can use a string instead.
            $link->text = get_string('clickhere', 'filter_gurls'); // Required.
            echo html_writer::tag('p', get_string('nogroups', 'filter_gurls') .
                    $OUTPUT->action_link($link->url, $link->text), array());
        }
    }

    public function filter_gurl_getmapgroups($mappinggid) {
        global $DB;
        $groupsstring = '';
        $sql = "SELECT gw.id, gw.groupid, gp.name
           FROM {filter_gurls_wassoc} gw
           LEFT JOIN {groups} gp on (gw.groupid=gp.id)
           WHERE gw.urlid= :urlid ";
        $params = array('urlid' => $mappinggid);
        $assgroups = $DB->get_records_sql($sql, $params);
        if ($assgroups) {
            foreach ($assgroups as $grupul) {
                $groupsstring .= $grupul->name . ',';
            }
            $groupsstring = mb_substr($groupsstring, 0, -1);
            return ' => ' . $groupsstring;
        }
        return $groupsstring;
    }

    public function filter_gurl_getdefinedurls($groupingid) {
        global $DB;

        $sql = "SELECT gu.id as gid, gu.*
           FROM {filter_gurls_urls} gu
           LEFT JOIN {filter_gurls_default} gd on (gu.defaultid=gd.id)
           WHERE gd.groupingid= :groupingid ";
        $params = array('groupingid' => $groupingid);
        $definedurls = $DB->get_records_sql($sql, $params);
        if ($definedurls) {
            return $definedurls;
        }
        return 0;
    }

    public function filter_gurl_assocgetgroups($sgurlid, $sgurldefaultid) {
        global $DB;

        $sql = "SELECT gr.*
                FROM {filter_gurls_wassoc} ga
                LEFT JOIN {groups} gr on (ga.groupid=gr.id)
                WHERE ga.urlid= :urlid AND ga.defaultid=defaultid ";
        $params = array('urlid' => $sgurlid, 'defaultid' => $sgurldefaultid);
        $assocgroups = $DB->get_records_sql($sql, $params);
        if ($assocgroups) {
            return $assocgroups;
        }
        return 0;
    }

    public function filter_gurl_getgroups($groupingid) {
        global $DB;
        $sql = "SELECT g.id, g.name
           FROM {groups} g
           LEFT JOIN {groupings_groups} gg ON (g.id=gg.groupid)
           WHERE gg.groupingid= :groupingid ";
        $params = array('groupingid' => $groupingid);
        $groups = $DB->get_records_sql($sql, $params);
        if ($groups) {
            return $groups;
        }
        return 0;
    }

    public function filter_gurl_urltable($defaultid) {
        global $DB, $CFG, $OUTPUT, $USER, $PAGE;
        $PAGE->requires->js(new moodle_url('http://cdnjs.cloudflare.com/ajax/libs/jquery/1.9.1/jquery.min.js'));
        $PAGE->requires->js(new moodle_url('http://cdnjs.cloudflare.com/ajax/libs/jinplace/1.2.1/jinplace.min.js'));
        $PAGE->requires->js('/filter/gurls/gurls.js');
        $updatepref = optional_param('updatepref', 0, PARAM_BOOL);

        echo html_writer::tag('h3', 'GURLS', array());
        echo html_writer::tag('hr', '', array());

        if ($updatepref) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage;
            $filter = optional_param('filter', 0, PARAM_INT);
            set_user_preference('gurls_perpage', $perpage);
            set_user_preference('gurls_filter', $filter);
        }

        $perpage = get_user_preferences('gurls_perpage', 10);
        $filter = get_user_preferences('gurls_filter', 0);
        $page = optional_param('page', 0, PARAM_INT);

        $defaulturlobj = $DB->get_record('filter_gurls_default', array('id' => $defaultid));
        if ($defaulturlobj) {
            echo html_writer::tag('br', '', array());
            $editurl = new moodle_url('editdefault.php',
                    array('groupingid' => $defaulturlobj->groupingid,
                        'defaultid' => $defaulturlobj->id, 'sesskey' => $USER->sesskey));
            $action = $OUTPUT->action_icon($editurl, new pix_icon('i/edit', get_string('editdefaulturl', 'filter_gurls')));
            echo html_writer::tag('span', get_string('default', 'filter_gurls'), array());
            echo html_writer::tag('span', $defaulturlobj->defaulturl, array('class' => 'editable',
                'data-url' => 'editdefaultinline.php?groupingid=' .
                $defaulturlobj->groupingid . '&defaultid=' . $defaulturlobj->id . '&sesskey=' . $USER->sesskey,
                'data-activator' => '#edit-activator',
                'data-input-class' => 'short'));
            echo html_writer::tag('span', $action, array('class' => 'button', 'id' => 'edit-activator'));
            echo html_writer::tag('br', '', array());
        }

        $sqlrep = "SELECT *
           FROM {filter_gurls_urls}
           WHERE defaultid= :defaultid ";
        $paramsrep = array('defaultid' => $defaultid);
        $allgurls = $DB->get_records_sql($sqlrep, $paramsrep);
        // Generate table...
        $tablecolumns = array('name', 'urlbase', 'associatedgroups', 'actions');
        $tableheaders = array(
            get_string('nameserver', 'filter_gurls'),
            get_string('urlbase', 'filter_gurls'),
            get_string('associatedgroups', 'filter_gurls'),
            get_string('actionsurl', 'filter_gurls'),
        );

        $table = new flexible_table('filter-gurls-groupings');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot . '/filter/gurls/defineurl.php?defaultid=' .
                $defaultid . '&groupingid=' . $defaulturlobj->groupingid . '&sesskey=' . $USER->sesskey);

        $table->sortable(true, 'name'); // Sorted by reportname by default.
        $table->collapsible(false);
        $table->initialbars(true);

        // Was : $table->column_suppress('picture'); ..

        $table->column_class('name', 'name');
        $table->column_class('urlbase', 'urlswap');
        $table->column_class('actions', 'description');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'mappings');
        $table->set_attribute('width', '100%');

        $table->no_sorting('urlbase');
        $table->no_sorting('associatedgroups');
        $table->no_sorting('actions');
        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();
        // Construct the SQL..

        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if (isset($allgurls)) {
            $where .= ' WHERE defaultid= ' . $defaultid . '  ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $sort;
        }

        if (!empty($allgurls)) {
            $select = "SELECT *  ";
            $sql = 'FROM {filter_gurls_urls} ';

            $sgurls = $DB->get_records_sql($select . $sql . $where . $sort, $params,
                    $table->get_page_start(), $table->get_page_size());
            $table->pagesize($perpage, count($sgurls));
            $offset = $page * $perpage;
            $rowclass = null;

            $endposition = $offset + $perpage;
            $currentposition = 0;

            foreach ($sgurls as $sgurl) {

                if ($currentposition == $offset && $offset < $endposition) {
                    $deletestatus = 1;
                    $mapgroup = '';
                    if ($groups = $this->filter_gurl_assocgetgroups($sgurl->id, $sgurl->defaultid)) {
                        foreach ($groups as $group) {
                            $mapgroup .= $group->name . '<br/>';
                        }
                        $deletestatus = 0;
                    }
                    $actions = '';
                    $actions .= ' ';
                    if ($deletestatus) {
                        $deleteurl = new moodle_url('deleteurl.php',
                                array('defaultid' => $defaultid, 'defurlid' => $sgurl->id,
                                    'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
                        $actions .= $OUTPUT->action_icon($deleteurl,
                                new pix_icon('t/delete', get_string('deletedefinedurl', 'filter_gurls')));
                    }
                    $assocgroup = new moodle_url('associategroups.php',
                            array('defaultid' => $defaultid, 'defurlid' => $sgurl->id,
                                'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
                    $actions .= $OUTPUT->action_icon($assocgroup,
                            new pix_icon('t/groups', get_string('associategroups', 'filter_gurls')));

                    $row = array($sgurl->name, $sgurl->urlbase, $mapgroup, $actions);
                    $offset++;
                    $table->add_data($row, $rowclass);
                }
                $currentposition++;
            }
            $table->totalrows = count($allgurls);
            echo $table->print_html();
        }

        $addurl = new moodle_url('/filter/gurls/definenewurl.php',
                array('defaultid' => $defaultid,
                    'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
        $closeurl = new moodle_url('/filter/gurls/gurlpanel.php', array());
        echo html_writer::tag('div', $OUTPUT->single_button($addurl, get_string('definenewurl', 'filter_gurls'), 'GET') .
                $OUTPUT->single_button($closeurl, get_string('close', 'filter_gurls'), 'GET'), array());
    }

    public function filter_gurl_assoctable($defaultid) {
        global $DB, $CFG, $OUTPUT, $USER;
        $updatepref = optional_param('updatepref', 0, PARAM_BOOL);

        if ($updatepref) {
            $perpage = optional_param('perpage', 10, PARAM_INT);
            $perpage = ($perpage <= 0) ? 10 : $perpage;
            $filter = optional_param('filter', 0, PARAM_INT);
            set_user_preference('gurls_perpage', $perpage);
            set_user_preference('gurls_filter', $filter);
        }

        $perpage = get_user_preferences('gurls_perpage', 10);
        $filter = get_user_preferences('gurls_filter', 0);
        $page = optional_param('page', 0, PARAM_INT);

        $defaulturlobj = $DB->get_record('filter_gurls_default', array('id' => $defaultid));

        $sqlrep = "SELECT wa.id as waid, ur.name as uname, ur.urlbase, g.name
           FROM {filter_gurls_wassoc} wa
           LEFT JOIN {filter_gurls_urls} ur on (wa.urlid = ur.id)
           LEFT JOIN {groups} g on (wa.groupid=g.id)
           WHERE wa.defaultid= :defaultid AND ur.defaultid= :defid  ";

        $paramsrep = array('defaultid' => $defaultid, 'defid' => $defaultid);
        $allgurls = $DB->get_records_sql($sqlrep, $paramsrep);
        // Generate table...
        $tablecolumns = array('name', 'urlname', 'urlbase', 'actions');
        $tableheaders = array(
            get_string('groupname', 'filter_gurls'),
            get_string('urlname', 'filter_gurls'),
            get_string('urlbase', 'filter_gurls'),
            get_string('actionsurl', 'filter_gurls'),
        );

        $table = new flexible_table('filter-gurls-wassocc');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot . '/filter/gurls/mapgroups.php');

        $table->sortable(true, 'name'); // Sorted by reportname by default.
        $table->collapsible(true);
        $table->initialbars(true);

        // Was : $table->column_suppress('picture'); ..

        $table->column_class('name', 'name');
        $table->column_class('urlname', 'urlname');
        $table->column_class('urlbase', 'urlbase');
        $table->column_class('actions', 'description');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'mappings');
        $table->set_attribute('width', '100%');

        $table->no_sorting('urlbase');
        $table->no_sorting('urlname');
        $table->no_sorting('actions');
        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();
        // Construct the SQL..

        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if (isset($allgurls)) {
            $where .= ' WHERE wa.defaultid= ' . $defaultid . ' AND  ur.defaultid=' . $defaultid . ' ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY ' . $sort;
        }

        if (!empty($allgurls)) {
            $select = "SELECT wa.id as waid, ur.name as uname, ur.urlbase, g.name   ";
            $sql = 'FROM {filter_gurls_wassoc} wa
                    LEFT JOIN {filter_gurls_urls} ur on (wa.urlid = ur.id)
                    LEFT JOIN {groups} g on (wa.groupid=g.id) ';

            $sgurls = $DB->get_records_sql($select . $sql . $where . $sort, $params,
                    $table->get_page_start(), $table->get_page_size());
            $table->pagesize($perpage, count($sgurls));
            $offset = $page * $perpage;
            $rowclass = null;

            $endposition = $offset + $perpage;
            $currentposition = 0;

            foreach ($sgurls as $sgurl) {
                if ($currentposition == $offset && $offset < $endposition) {
                    $actions = '';
                    $define = '';
                    $editurl = new moodle_url('editassoc.php',
                            array('defaultid' => $defaultid, 'waid' => $sgurl->waid,
                                'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
                    $actions = $OUTPUT->action_icon($editurl,
                            new pix_icon('i/edit', get_string('editassoc', 'filter_gurls')));
                    $actions .= ' ';
                    $deleteurl = new moodle_url('deleteassoc.php',
                            array('defaultid' => $defaultid, 'waid' => $sgurl->waid,
                                'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
                    $actions .= $OUTPUT->action_icon($deleteurl,
                            new pix_icon('t/delete', get_string('deleteassoc', 'filter_gurls')));

                    $row = array($sgurl->name, $sgurl->uname, $sgurl->urlbase, $actions);
                    $offset++;
                    $table->add_data($row, $rowclass);
                }
                $currentposition++;
            }
            $table->totalrows = count($allgurls);
            echo $table->print_html();
        } else {
            echo html_writer::tag('div', get_string('noassoc', 'filter_gurls'), array('class' => 'nosubmisson'));
        }

        $addurl = new moodle_url('/filter/gurls/defineassoc.php',
                array('defaultid' => $defaultid, 'groupingid' => $defaulturlobj->groupingid, 'sesskey' => $USER->sesskey));
        echo $OUTPUT->single_button($addurl, get_string('defineassoc', 'filter_gurls'), 'GET');
    }

}