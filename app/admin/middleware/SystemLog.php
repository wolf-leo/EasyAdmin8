<?php

namespace app\admin\middleware;

use app\admin\service\SystemLogService;
use app\Request;
use app\admin\service\tool\CommonTool;
use think\facade\Log;

/**
 * 系统操作日志中间件
 * Class SystemLog
 * @package app\admin\middleware
 */
class SystemLog
{

    /**
     * 敏感信息字段，日志记录时需要加密
     * @var array
     */
    protected $sensitiveParams = [
        'password',
        'password_again',
        'phone',
        'mobile',
    ];

    public function handle(Request $request, \Closure $next)
    {
        $params = $request->param();
        if (isset($params['s'])) {
            unset($params['s']);
        }
        foreach ($params as $key => $val) {
            in_array($key, $this->sensitiveParams) && $params[$key] = "***********";
        }
        $method = strtolower($request->method());
        $url    = $request->url();

        trace([
                  'url'    => $url,
                  'method' => $method,
                  'params' => $params,
              ],
              'requestDebugInfo'
        );

        if ($request->isAjax()) {
            if (in_array($method, ['post', 'put', 'delete'])) {
                $ip   = CommonTool::getRealIp();
                $data = [
                    'admin_id'    => session('admin.id'),
                    'url'         => $url,
                    'method'      => $method,
                    'ip'          => $ip,
                    'content'     => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'useragent'   => $_SERVER['HTTP_USER_AGENT'],
                    'create_time' => time(),
                ];
                SystemLogService::instance()->save($data);
            }
        }
        return $next($request);
    }

}