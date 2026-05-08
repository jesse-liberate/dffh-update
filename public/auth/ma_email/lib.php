<?php

function insert_action($action) {
    global $DB;

    $action->id = $DB->insert_record('ma_email_records', $action);
}

function get_actions($userid, $action = null) {
    global $DB;

    $action_sql = '';
    $action_params = [];
    if (!empty($action)) {
        $action_sql = ' AND er.action = ?';
        $action_params = [$action];
    }

    $query = "SELECT er.*, u.firstname as admin_firstname, u.lastname as admin_lastname
        FROM {ma_email_records} er
        LEFT JOIN {user} u ON er.adminid = u.id
        WHERE er.userid = ?
        $action_sql
        ORDER BY er.timecreated DESC";

    return $DB->get_records_sql($query, array_merge([$userid], $action_params));
}

function get_pending_users() {
    global $DB;

    return $DB->get_records('user', array(
        'auth' => 'ma_email',
        'confirmed' => 0,
        'deleted' => 0,
        'suspended' => 0,
    ));
}