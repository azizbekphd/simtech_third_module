<?php

$schema['departments.update'] = [
    'func' => ['fn_get_department_name', '@department_id'],
    'text' => 'departments'
];

return $schema;

