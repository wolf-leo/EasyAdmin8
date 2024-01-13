<?php

namespace app\admin\middleware;

use app\Request;

/**
 *  检测是否安装成功
 *  系统安装后可以在 config/route 中删除该中间件判定
 */
class CheckInstall
{
    public function handle(Request $request, \Closure $next)
    {
        $controller = $request->controller();
        if (!is_file(ROOT_PATH . 'config' . DS . 'install' . DS . 'lock' . DS . 'install.lock')) {
            if ($controller != 'Install') return redirect('/install');
        }
        return $next($request);
    }
}