<?php

if ( !defined('BOOTSTRAP') ) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Languages\Languages;
use Tygh\BlockManager\Block;
use Tygh\Tools\SecurityHelper;
use Tygh\Enum\SiteArea;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Enum\ImagePairTypes;

/**
 * Returns a list of departments
 *
 * @param array  $params         Search params
 * @param string $lang_code      Language code
 * @param int    $items_per_page Items per page
 */
function fn_get_departments($params = [], $lang_code = CART_LANGUAGE, $items_per_page = 0)
{
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];

    $params = array_merge($default_params, $params);

    if (AREA === SiteArea::STOREFRONT) {
        $params['status'] = ObjectStatuses::ACTIVE;
    }

    $sortings = [
        'timestamp' => '?:departments.timestamp',
        'name' => '?:department_names.name',
        'description' => '?:department_description.description',
        'status' => '?:departments.status',
    ];

    $condition = $limit = $join = '';

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    if (!empty($params['name'])) {
        $condition .= db_quote(' AND ?:department_names.name LIKE ?l', '%' .
            trim($params['name']) . '%');
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND ?:departments.status = ?s', $params['status']);
    }

    $fields = [
        '?:departments.department_id',
        '?:departments.supervisor_id',
        '?:departments.status',
        '?:departments.timestamp',
        '?:department_names.name',
        '?:department_descriptions.description',
        '?:department_images.department_image_id',
    ];

    $join .= db_quote(' LEFT JOIN ?:department_names ' .
        'ON ?:department_names.department_id = ?:departments.department_id ' .
        'AND ?:department_names.lang_code = ?s', $lang_code);
    $join .= db_quote(' LEFT JOIN ?:department_descriptions ' .
        'ON ?:department_descriptions.department_id = ?:departments.department_id ' .
        'AND ?:department_descriptions.lang_code = ?s', $lang_code);
    $join .= db_quote(' LEFT JOIN ?:department_images ' .
        'ON ?:department_images.department_id = ?:departments.department_id ' .
        'AND ?:department_images.lang_code = ?s', $lang_code);

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field('SELECT COUNT(*) FROM ?:departments ' .
            '$join WHERE 1 $condition');
        $limit = db_paginate($params['page'], $params['items_per_page'],
            $params['total_items']);
    }

    $departments = db_get_hash_array(
        'SELECT ?p FROM ?:departments ' .
        $join .
        'WHERE 1 ?p ?p ?p',
        'department_id', implode(', ', $fields), $condition, $sorting, $limit
    );

    if (!empty($departments)) {
        foreach ($departments as &$department) {
            $department['main_pair'] = fn_get_image_pairs($department['department_image_id'],
                'department_logos', ImagePairTypes::MAIN, true, false, $lang_code);
            if (AREA !== SiteArea::STOREFRONT) continue;
            $department['supervisor_data'] = !empty($department['supervisor_id']) ?
                fn_get_user_short_info($department['supervisor_id']) : [];
        }
    }

    return [$departments, $params];
}

/**
 * Get specific department data
 *
 * @param int    $department_id Department id
 * @param string $lang_code     Language code
 */
function fn_get_department_data($department_id, $lang_code = CART_LANGUAGE)
{
    $joins = [];

    $fields = [
        '?:departments.department_id',
        '?:departments.status',
        '?:department_names.name',
        '?:departments.timestamp',
        '?:departments.upd_timestamp',
        '?:departments.supervisor_id',
        '?:department_descriptions.description',
        '?:department_images.department_image_id',
    ];

    $joins[] = db_quote('LEFT JOIN ?:department_names ON ' .
        '?:department_names.department_id = ?:departments.department_id ' .
        'AND ?:department_names.lang_code = ?s', $lang_code);
    $joins[] = db_quote('LEFT JOIN ?:department_descriptions ON ' .
        '?:department_descriptions.department_id = ?:departments.department_id ' .
        'AND ?:department_descriptions.lang_code = ?s', $lang_code);
    $joins[] = db_quote('LEFT JOIN ?:department_images ON ' .
        '?:department_images.department_id = ?:departments.department_id ' .
        'AND ?:department_images.lang_code = ?s', $lang_code);

    $condition = db_quote('WHERE ?:departments.department_id = ?i', $department_id);

    $department = db_get_row('SELECT ' . implode(', ', $fields) .
        ' FROM ?:departments ' . implode(' ', $joins) .' $condition');

    if (!empty($department)) {
        $department['main_pair'] = fn_get_image_pairs($department['department_image_id'],
            'department_logos', ImagePairTypes::MAIN, true, false, $lang_code);
        $department['supervisor_data'] = !empty($department['supervisor_id']) ?
            fn_get_user_short_info($department['supervisor_id']) : [];
        $department['employee_ids'] = fn_get_department_employee_ids(
            $department['department_id']);
        if (AREA === SiteArea::STOREFRONT) {
            $department['employees'] = [];
            foreach ($department['employee_ids'] as $employee_id) {
                $department['employees'][] = fn_get_user_short_info($employee_id);
            }
        }
    }

    return $department;
}

/**
 * Deletes department and all related data
 *
 * @param int $department_id Department id
 */
function fn_delete_department_by_id($department_id)
{
    if (!empty($department_id) && fn_check_company_id('departments', 'department_id',
        $department_id)) {
        db_query('DELETE FROM ?:departments WHERE department_id = ?i', $department_id);
        db_query('DELETE FROM ?:department_descriptions WHERE department_id = ?i',
            $department_id);
        db_query('DELETE FROM ?:department_names WHERE department_id = ?i', $department_id);
        db_query('DELETE FROM ?:department_employee WHERE department_id = ?i', $department_id);

        fn_set_hook('delete_departments', $department_id);

        Block::instance()->removeDynamicObjectData('departments', $department_id);

        $department_images_ids = db_get_fields('SELECT department_image_id FROM ' .
            '?:department_images WHERE department_id = ?i', $department_id);

        foreach ($department_images_ids as $department_image_id) {
            fn_delete_image_pairs($department_image_id, 'department_logos');
        }

        db_query('DELETE FROM ?:department_images WHERE department_id = ?i', $department_id);
    }
}

/**
 * Checks of request for need to update the department image
 *
 * @return bool
 */
function fn_departments_need_image_update()
{
    if (!empty($_REQUEST['file_departments_main_image_icon']) &&
        is_array($_REQUEST['file_departments_main_image_icon'])) {
        $image_department = reset($_REQUEST['file_departments_main_image_icon']);

        if ($image_department === 'departments_main') {
            return false;
        }
    }

    return true;
}

/**
 * Update department data
 *
 * @param array  $data          New department data
 * @param int    $department_id Department id
 * @param string $lang_code     Language code
 */
function fn_departments_update_department($data, $department_id, $lang_code = DESCR_SL)
{
    SecurityHelper::sanitizeObjectData('department', $data);

    $data['upd_timestamp'] = TIME;

    $data['localization'] = empty($data['localization']) ? '' :
        fn_implode_localizations($data['localization']);

    if (!empty($department_id)) {
        db_query('UPDATE ?:departments SET ?u WHERE department_id = ?i', $data, $department_id);
        db_query('UPDATE ?:department_names SET ?u WHERE department_id = ?i ' .
            'AND lang_code = ?s', $data, $department_id, $lang_code);
        db_query('UPDATE ?:department_descriptions SET ?u WHERE department_id = ?i ' .
            'AND lang_code = ?s', $data, $department_id, $lang_code);
        
        $department_employee_ids = explode(',', $data['employee_ids']);
        db_query('DELETE FROM ?:department_employee WHERE department_id = ?i ' .
            'AND user_id NOT IN (?n)', $department_id, $department_employee_ids);
        $existing_employee_ids = fn_get_department_employee_ids($department_id);
        $new_department_employees = [];
        foreach ($department_employee_ids as $employee_id) {
            if (!in_array($employee_id, $existing_employee_ids)) {
                $new_department_employees[] = $employee_id;
            }
        }
        fn_add_department_employees($department_id, $new_department_employees);

        $department_image_id = fn_get_department_image_id($department_id, $lang_code);
        $department_image_exist = !empty($department_image_id);
        $department_is_multilang = Registry::get('addons.departments.department_multilang')
            === YesNo::YES;
        $image_is_update = fn_departments_need_image_update();

        if ($department_is_multilang) {
            if ($department_image_exist && $image_is_update) {
                fn_delete_image_pairs($department_image_id, 'department_logos');
                db_query('DELETE FROM ?:department_images WHERE department_id = ?i ' .
                    'AND lang_code = ?s', $department_id, $lang_code);
                $department_image_exist = false;
            }
        } else {
            if (isset($data['url'])) {
                db_query('UPDATE ?:department_descriptions SET url = ?s ' .
                    'WHERE department_id = ?i', $data['url'], $department_id);
            }
        }

        if ($image_is_update && !$department_image_exist) {
            $department_image_id = db_query('INSERT INTO ?:department_images ' .
                '(department_id, lang_code) VALUE(?i, ?s)', $department_id, $lang_code);
        }
        $pair_data = fn_attach_image_pairs('departments_main', 'department_logos',
            $department_image_id, $lang_code);

        if (!$department_is_multilang && !$department_image_exist) {
            fn_departments_image_all_links($department_id, $pair_data, $lang_code);
        }

    } else {
        $data['timestamp'] = TIME;

        $department_id = $data['department_id'] = db_query('REPLACE INTO ?:departments ?e',
            $data);

        $employees = [];
        foreach (explode(',', $data['employee_ids']) as $employee_id) {
            $employees[] = [
                'user_id' => $employee_id,
                'department_id' => $department_id,
            ];
        }

        foreach (Languages::getAll() as $data['lang_code'] => $v) {
            db_query('REPLACE INTO ?:department_descriptions ?e', $data);
            db_query('REPLACE INTO ?:department_names ?e', $data);
        }
        db_query('REPLACE INTO ?:department_employee ?m', $employees);

        if (fn_departments_need_image_update()) {
            $department_image_id = db_get_next_auto_increment_id('department_images');
            $pair_data = fn_attach_image_pairs('departments_main', 'department_logos',
                $department_image_id, $lang_code);
            if (!empty($pair_data)) {
                $data_department_image = [
                    'department_image_id' => $department_image_id,
                    'department_id'       => $department_id,
                    'lang_code'       => $lang_code
                ];

                db_query('INSERT INTO ?:department_images ?e', $data_department_image);
                fn_departments_image_all_links($department_id, $pair_data, $lang_code);
            }
        }
    }

    return $department_id;
}

/**
 * Adds image links
 *
 * @param int    $department_id  Department id
 * @param array  $pair_data      Image pair data
 * @param string $main_lang_code Language code
 */
function fn_departments_image_all_links($department_id, $pair_data, $main_lang_code = DESCR_SL)
{
    if (!empty($pair_data)) {
        $pair_id = reset($pair_data);

        $lang_codes = Languages::getAll();
        unset($lang_codes[$main_lang_code]);

        foreach ($lang_codes as $lang_code => $lang_data) {
            $_department_image_id = db_query('INSERT INTO ?:department_images ' .
                '(department_id, lang_code) VALUE(?i, ?s)', $department_id, $lang_code);
            fn_add_image_link($_department_image_id, $pair_id);
        }
    }
}

/**
 * Get department image data
 *
 * @param int    $department_id Department id
 * @param string $lang_code     Language code
 */
function fn_get_department_image_id($department_id, $lang_code = DESCR_SL)
{
    return db_get_field('SELECT department_image_id FROM ?:department_images ' .
        'WHERE department_id = ?i AND lang_code = ?s', $department_id, $lang_code);
}

/**
 * Get department name
 *
 * @param int    $department_id Department id
 * @param string $lang_code     Language code
 */
function fn_get_department_name($department_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($department_id)) {
        return db_get_field('SELECT `name` FROM ?:department_names ' .
            'WHERE department_id = ?i AND lang_code = ?s', $department_id, $lang_code);
    }

    return false;
}

/**
 * Get identificators of department employees
 *
 * @param int $department_id Department id
 */
function fn_get_department_employee_ids($department_id)
{
    $condition = db_quote('AND department_id = ?i', $department_id);

    return db_get_fields(
        'SELECT ?p FROM ?:department_employee ' .
        'WHERE 1 ?p',
        '?:department_employee.user_id', $condition
    );
}

/**
 * Add employees to department
 *
 * @param $department_id Department id
 * @param $employee_ids  Employee ids
 */
function fn_add_department_employees($department_id, $employee_ids)
{
    if (empty($employee_ids)) {
        return;
    }
    $employees = [];
    foreach ($employee_ids as $employee_id) {
        $employees[] = [
            'user_id' => $employee_id,
            'department_id' => $department_id,
        ];
    }
    return db_query('REPLACE INTO ?:department_employee ?m ', $employees);
}

