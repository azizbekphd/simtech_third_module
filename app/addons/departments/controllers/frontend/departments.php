<?php

use Tygh\Registry;

if ($mode === 'view') {

    $params = $_REQUEST;

    if ($items_per_page = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'items_per_page')) {
        $params['items_per_page'] = $items_per_page;
    }
    if ($sort_by = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'sort_by')) {
        $params['sort_by'] = $sort_by;
    }
    if ($sort_order = fn_change_session_param(Tygh::$app['session'], $_REQUEST, 'sort_order')) {
        $params['sort_order'] = $sort_order;
    }

    $params['user_id'] =  Tygh::$app['session']['auth']['user_id'];

    list($departments, $search) = fn_get_departments(
        $params, CART_LANGUAGE,
        Registry::get('settings.Appearance.products_per_page'));
    $columns = 3;

    Tygh::$app['view']->assign('departments', $departments);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('columns', $columns);

    fn_add_breadcrumb(__("departments.title"));

} elseif ($mode === 'details') {

    $department = fn_get_department_data($_REQUEST['department_id'], DESCR_SL);

    if (empty($department)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign('department', $department);

    fn_add_breadcrumb(__("departments.title"), "?dispatch=departments.view");
    fn_add_breadcrumb($department["name"]);
}

