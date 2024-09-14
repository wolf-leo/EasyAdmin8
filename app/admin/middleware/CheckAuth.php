<?php

namespace app\admin\middleware;

use app\common\service\AuthService;
use app\common\traits\JumpTrait;
use app\Request;
use Closure;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class CheckAuth
{
    use JumpTrait;

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function handle(Request $request, Closure $next)
    {
        $adminUserInfo = $request->adminUserInfo;
        if (empty($adminUserInfo)) return $next($request);
        $adminConfig = config('admin');
        $adminId     = $adminUserInfo['id'];

        $authService       = app(AuthService::class, ['adminId' => $adminId]);
        $currentNode       = $authService->getCurrentNode();
        $currentController = parse_name($request->controller());

        if (!in_array($currentController, $adminConfig['no_auth_controller']) && !in_array($currentNode, $adminConfig['no_auth_node'])) {
            $check = $authService->checkNode($currentNode);
            !$check && $this->error('无权限访问');
            // 判断是否为演示环境
            if (env('EASYADMIN.IS_DEMO', false) && $request->isPost()) {
                $this->error('演示环境下不允许修改');
            }
        }
        return $next($request);
    }
}