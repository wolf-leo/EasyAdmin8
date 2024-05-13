<?php

use app\admin\middleware\CheckInstall;
use app\admin\middleware\CheckLogin;
use app\admin\middleware\CheckAuth;
use app\admin\middleware\SystemLog;

// 你可以在这里继续写你需要的路由


// +----------------------------------------------------------------------
// | 这里只是路由的中间件
// | 至于为什么要把中间件配置写在这里呢??? Why???
// | 因为 ThinkPHP官方最新版本 已经不支持在中间件获取 controller 和 action 了
// +----------------------------------------------------------------------

return [
    'middleware' => [
        // 判断是否已经安装后台系统
        CheckInstall::class,
        // 检测是否登录
        CheckLogin::class,
        // 验证节点权限
        CheckAuth::class,
        // 操作日志
        SystemLog::class,
    ],
];