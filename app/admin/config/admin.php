<?php

return [
    // 后台路径地址 默认 admin
    'alias_name'         => env('EASYADMIN.ADMIN'),

    // 不需要验证权限的控制器
    'no_auth_controller' => [
        'ajax',
        'login',
        'index',
    ],

    // 不需要验证权限的节点
    'no_auth_node'       => [
        'login/index',
        'login/out',
    ],

    //上传类型
    'upload_types'       => [
        'local' => '本地存储',
        'oss'   => '阿里云oss',
        'cos'   => '腾讯云cos',
        'qnoss' => '七牛云'
    ],

    // 默认编辑器
    'editor_types'       => [
        'ueditor'    => '百度编辑器(不建议使用)',
        'ckeditor'   => 'CK编辑器',
        'wangEditor' => 'wangEditor(推荐使用)',
    ],

];