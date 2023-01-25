<?php

use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Enum\UsergroupTypes;

if (isset($_REQUEST['for_departments'])) {

    $auth = & Tygh::$app['session']['auth'];
    
    if ($mode === 'picker') {
        $params = $_REQUEST;
        $params['skip_view'] = YesNo::YES;
    
        list($users, $search) = fn_get_users($params, $auth, Registry::get('settings.Appearance.admin_elements_per_page'));
        Tygh::$app['view']->assign([
            'users' => $users,
            'search' => $search,
            'countries' => fn_get_simple_countries(true),
            'states' => fn_get_all_states(),
            'usergroups' => fn_get_usergroups(
                ['status' => [
                    UsergroupTypes::TYPE_ADMIN,
                    UsergroupTypes::TYPE_CUSTOMER,
                ]]),
        ]);
    
        Tygh::$app['view']->display('pickers/users/picker_contents.tpl');
        exit;
    }
}

