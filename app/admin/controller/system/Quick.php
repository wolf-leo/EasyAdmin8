<?php

namespace app\admin\controller\system;


use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="快捷入口管理")
 * Class Quick
 * @package app\admin\controller\system
 */
class Quick extends AdminController
{

    use \app\admin\traits\Curd;

    protected array $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemQuick();
    }

}