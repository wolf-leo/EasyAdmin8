<?php

namespace app\admin\middleware;

use app\admin\service\ConfigService;
use app\common\constants\AdminConstant;
use think\App;
use think\facade\Request;
use think\facade\View;

/**
 * @deprecated  废弃，新版TP不支持在中间件获取控制器相关信息
 * Class ViewInit
 * @package app\admin\middleware
 */
class ViewInit
{

    public function handle(\app\Request $request, \Closure $next)
    {
        list($thisModule, $thisController, $thisAction) = [app('http')->getName(), Request::controller(), $request->action()];
        list($thisControllerArr, $jsPath) = [explode('.', $thisController), null];
        foreach ($thisControllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }
        $autoloadJs = file_exists(root_path('public')."static/{$thisModule}/js/{$jsPath}.js") ? true : false;
        $thisControllerJsPath = "{$thisModule}/js/{$jsPath}.js";
        $adminModuleName = config('app.admin_alias_name');
        $isSuperAdmin = session('admin.id') == AdminConstant::SUPER_ADMIN_ID ? true : false;
        $data = [
            'adminModuleName'      => $adminModuleName,
            'thisController'       => parse_name($thisController),
            'thisAction'           => $thisAction,
            'thisRequest'          => parse_name("{$thisModule}/{$thisController}/{$thisAction}"),
            'thisControllerJsPath' => "{$thisControllerJsPath}",
            'autoloadJs'           => $autoloadJs,
            'isSuperAdmin'         => $isSuperAdmin,
            'version'              => env('APP_DEBUG') ? time() : ConfigService::getVersion(),
        ];

        View::assign($data);
        $request->adminModuleName = $adminModuleName;
        return $next($request);
    }


}