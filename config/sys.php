<?php

return [

    //登录处理用哪个处理器来处理
    'login_process' => 'default',

    //用户多久(秒)不操作后会注销登陆
    'sys_session_lefttime' => 7200,

    //后台访问域名，不用http://开头
    'sys_admin_domain' => 'local.yushi.com/admin',

    //博客访问域名
    'sys_blog_domain' => 'local.yushi.com',

    //上传的路径，包括ueditor的上传路径也在这里定义了，因为修改了ueditor，重新加载了这个文件。
    'sys_upload_path' => __DIR__ . '/../../upload_path',

    //水印图片
    'sys_water_file' => __DIR__ . '/../storage/water/water.png',

    //不需要验证权限的功能，*号代表全部, module不能为*号，module和class都不能为数组
    'access_public' => [
        ['module' => 'foundation', 'class' => 'index', 'function' => '*'],
        ['module' => 'foundation', 'class' => 'user', 'function' => ['mpassword']],
        ['module' => 'foundation', 'class' => 'upload', 'function' => ['process']],
    ]
];