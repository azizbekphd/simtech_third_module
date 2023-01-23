<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD']	=== 'POST') {

    fn_trusted_vars('departments', 'department_data');
    $suffix = '';

    //
    // Delete departments
    //
    if ($mode === 'm_delete') {
        foreach ($_REQUEST['department_ids'] as $v) {
            fn_delete_department_by_id($v);
        }

        $suffix = '.manage';
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['department_ids'])
        && is_array($_REQUEST['department_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = (string) $_REQUEST['status'];

        foreach ($_REQUEST['department_ids'] as $department_id) {
            fn_tools_update_status([
                'table'             => 'departments',
                'status'            => $status_to,
                'id_name'           => 'department_id',
                'id'                => $department_id,
                'show_error_notice' => false
            ]);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('departments.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    //
    // Add/edit departments
    //
    if ($mode === 'update') {
        $department_id = fn_departments_update_department($_REQUEST['department_data'], $_REQUEST['department_id'], DESCR_SL);

        $suffix = ".update?department_id=$department_id";
    }

    if ($mode === 'delete') {
        if (!empty($_REQUEST['department_id'])) {
            fn_delete_department_by_id($_REQUEST['department_id']);
        }

        $suffix = '.manage';
    }

    return [CONTROLLER_STATUS_OK, 'departments' . $suffix];
}

if ($mode === 'update') {
    $department = fn_get_department_data($_REQUEST['department_id'], DESCR_SL);

    if (empty($department)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign('department', $department);

} elseif ($mode === 'manage' || $mode === 'picker') {

    list($departments, $params) = fn_get_departments($_REQUEST, DESCR_SL, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign([
        'departments'  => $departments,
        'search' => $params,
    ]);
}

//
// departments picker
//
if ($mode === 'picker') {
    Tygh::$app['view']->display('addons/departments/pickers/departments/picker_contents.tpl');
    exit;
}

