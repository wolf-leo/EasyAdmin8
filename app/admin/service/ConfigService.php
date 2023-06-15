<?php

namespace app\admin\service;

use think\facade\Cache;

class ConfigService
{

    public static function getVersion()
    {
        $version = Cache('version');
        if (empty($version)) {
            $version = sysconfig('site', 'site_version');
            cache('site_version', $version);
            Cache::set('version', $version, 3600);
        }
        return $version;
    }

}