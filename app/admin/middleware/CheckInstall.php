<?php

namespace app\admin\middleware;

use app\common\traits\JumpTrait;
use Closure;

class CheckInstall
{
    use JumpTrait;

    public function handle($request, Closure $next)
    {
        $controller = $request->controller();
        if (!is_file(root_path() . 'config' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR . 'install.lock')) {
            if ($controller != 'Install') return redirect('/install');
        }
        return $next($request);
    }
}