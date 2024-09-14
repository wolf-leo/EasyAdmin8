<?php

namespace app\admin\middleware;

use app\common\traits\JumpTrait;
use app\Request;
use Closure;
use ReflectionClass;
use ReflectionException;

class CheckLogin
{
    use JumpTrait;

    /**
     * @throws ReflectionException
     */
    public function handle(Request $request, Closure $next)
    {
        $controller = $request->controller();
        if (empty($controller)) return $next($request);
        if (str_contains($controller, '.')) $controller = str_replace('.', '\\', $controller);
        $action          = $request->action();
        $controllerClass = 'app\\admin\\controller\\' . $controller;
        $classObj        = new ReflectionClass($controllerClass);
        $properties      = $classObj->getDefaultProperties();
        $ignoreAuth      = $properties['ignoreAuth'] ?? false;
        $adminUserInfo   = session('admin');
        if (!$ignoreAuth) {
            $noNeedCheck = $properties['noNeedCheck'] ?? [];
            if (in_array($action, $noNeedCheck)) {
                return $next($request);
            }
            if (empty($adminUserInfo)) {
                return redirect(__url('login/index'));
            }
            // 判断是否登录过期
            $expireTime = $adminUserInfo['expire_time'];
            if ($expireTime !== true && time() > $expireTime) {
                session('admin', null);
                $this->error('登录已过期，请重新登录', [], __url(env('EASYADMIN.ADMIN') . '/login/index'));
            }
        }
        $request->adminUserInfo = $adminUserInfo ?: [];
        return $next($request);
    }
}