<?php

namespace app\admin\controller\system;

use app\admin\model\SystemUploadfile;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

/**
 * @ControllerAnnotation(title="上传文件管理")
 * @package app\admin\controller\system
 */
class Uploadfile extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemUploadfile();
        $this->assign('upload_types', config('admin.upload_types'));
    }

}