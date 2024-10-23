<?php

namespace app\index\controller;

use app\BaseController;
use think\facade\Env;
use think\response\Redirect;

class Index extends BaseController
{
    /**
     * @return Redirect
     */
    public function index(): Redirect
    {
        // 这是项目首页 系统默认跳转后台页面
        return redirect('/' . Env::get('EASYADMIN.ADMIN', 'admin'));
    }
}