<?php

return [
    // model
    'model.admin.permission' => '\App\Models\Admin\Permission',
    'model.admin.access' => '\App\Models\Admin\Access',
    'model.admin.user' => '\App\Models\Admin\User',
    'model.admin.group' => '\App\Models\Admin\Group',

    // process
    'process.admin.acl' => '\App\Services\Admin\Acl\Process',
    'process.admin.group' => '\App\Services\Admin\Group\Process',
    'process.admin.user' => '\App\Services\Admin\User\Process',

    // params
    'param.admin.aclsave' => '\App\Services\Admin\Acl\Param\AclSave',
    'param.admin.aclset' => '\App\Services\Admin\Acl\Param\AclSet',
    'param.admin.groupsave' => '\App\Services\Admin\Group\Param\GroupSave',
    'param.admin.usersave' => '\App\Services\Admin\User\Param\UserSave',
    'param.admin.usermp' => '\App\Services\Admin\User\Param\UserModifyPassword',

    // acl
    'admin.acl' => '\App\Services\Admin\Acl\Acl',

];