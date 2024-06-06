<?php

namespace app\admin\controller;

use app\admin\model\SystemUploadfile;
use app\admin\service\UploadService;
use app\common\controller\AdminController;
use app\common\service\MenuService;
use app\Request;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\facade\Cache;
use think\response\Json;

class Ajax extends AdminController
{

    /**
     * 初始化后台接口地址
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function initAdmin(): Json
    {
        $cacheData = Cache::get('initAdmin_' . $this->adminUid);
        if (!empty($cacheData)) {
            return json($cacheData);
        }
        $menuService = new MenuService($this->adminUid);
        $data        = [
            'logoInfo' => [
                'title' => sysConfig('site', 'logo_title'),
                'image' => sysConfig('site', 'logo_image'),
                'href'  => __url('index/index'),
            ],
            'homeInfo' => $menuService->getHomeInfo(),
            'menuInfo' => $menuService->getMenuTree(),
        ];
        Cache::tag('initAdmin')->set('initAdmin_' . $this->adminUid, $data);
        return json($data);
    }

    /**
     * 清理缓存接口
     */
    public function clearCache(): void
    {
        Cache::clear();
        $this->success('清理缓存成功');
    }

    /**
     * 上传文件
     * @param Request $request
     * @return Json|null
     */
    public function upload(Request $request): Json|null
    {
        $this->isDemo && $this->error('演示环境下不允许修改');
        $this->checkPostRequest();
        $type         = $request->param('type', '');
        $data         = [
            'upload_type' => $request->post('upload_type'),
            'file'        => $request->file($type == 'editor' ? 'upload' : 'file'),
        ];
        $uploadConfig = sysConfig('upload');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'upload_type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_type']}",
            'file|文件'                    => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        $this->validate($data, $rule);
        $upload_type = $uploadConfig['upload_type'];
        try {
            $upload = UploadService::instance()->setConfig($uploadConfig)->$upload_type($data['file'], $type);
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $code = $upload['code'] ?? 0;
        if ($code == 0) {
            $this->error($upload['data'] ?? '');
        }else {
            if ($type == 'editor') {
                return json(
                    [
                        'error'    => ['message' => '上传成功', 'number' => 201,],
                        'fileName' => '',
                        'uploaded' => 1,
                        'url'      => $upload['data']['url'] ?? '',
                    ]
                );
            }else {
                $this->success('上传成功', $upload['data'] ?? '');
            }
        }
    }

    /**
     * 获取上传文件列表
     * @param Request $request
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUploadFiles(Request $request): Json
    {
        $get         = $request->get();
        $page        = !empty($get['page']) ? $get['page'] : 1;
        $limit       = !empty($get['limit']) ? $get['limit'] : 10;
        $title       = !empty($get['title']) ? $get['title'] : null;
        $this->model = new SystemUploadfile();
        $count       = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
            ->count();
        $list        = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
            ->page($page, $limit)
            ->order($this->sort)
            ->select()->toArray();
        $data        = [
            'code'  => 0,
            'msg'   => '',
            'count' => $count,
            'data'  => $list,
        ];
        return json($data);
    }

    /**
     * 百度编辑器上传
     * @param Request $request
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function uploadUEditor(Request $request): Json
    {
        $uploadConfig      = sysConfig('upload');
        $upload_allow_size = $uploadConfig['upload_allow_size'];
        $_upload_allow_ext = explode(',', $uploadConfig['upload_allow_ext']);
        $upload_allow_ext  = [];
        array_map(function ($value) use (&$upload_allow_ext) {
            $upload_allow_ext[] = '.' . $value;
        }, $_upload_allow_ext);
        $config      = [
            // 上传图片配置项
            "imageActionName"         => "image",
            "imageFieldName"          => "file",
            "imageMaxSize"            => $upload_allow_size,
            "imageAllowFiles"         => $upload_allow_ext,
            "imageCompressEnable"     => true,
            "imageCompressBorder"     => 5000,
            "imageInsertAlign"        => "none",
            "imageUrlPrefix"          => "",
            // 列出图片
            "imageManagerActionName"  => "listImage",
            "imageManagerListSize"    => 20,
            "imageManagerUrlPrefix"   => "",
            "imageManagerInsertAlign" => "none",
            "imageManagerAllowFiles"  => $upload_allow_ext,
            // 上传 video
            "videoActionName"         => "video",
            "videoFieldName"          => "file",
            "videoUrlPrefix"          => "",
            "videoMaxSize"            => $upload_allow_size,
            "videoAllowFiles"         => $upload_allow_ext,
            // 上传 附件
            "fileActionName"          => "attachment",
            "fileFieldName"           => "file",
            "fileMaxSize"             => $upload_allow_size,
            "fileAllowFiles"          => $upload_allow_ext,
        ];
        $action      = $request->param('action/s', '');
        $file        = $request->file('file');
        $upload_type = $uploadConfig['upload_type'];
        switch ($action) {
            case 'image':
            case 'attachment':
            case 'video':
                if ($this->isDemo) return json(['state' => '演示环境下不允许修改']);
                try {
                    $upload = UploadService::instance()->setConfig($uploadConfig)->$upload_type($file);
                    $code   = $upload['code'] ?? 0;
                    if ($code == 0) {
                        return json(['state' => $upload['data'] ?? '上传错误信息']);
                    }else {
                        return json(['state' => 'SUCCESS', 'url' => $upload['data']['url'] ?? '']);
                    }
                }catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
                break;
            case 'listImage':
                $list   = (new SystemUploadfile())->order($this->sort)->limit(100)->field('url')->select()->toArray();
                $result = [
                    "state" => "SUCCESS",
                    "list"  => $list,
                    "total" => 0,
                    "start" => 0,
                ];
                return json($result);
            default:
                return json($config);
        }
    }
}