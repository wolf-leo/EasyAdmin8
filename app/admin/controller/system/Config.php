<?php

namespace app\admin\controller\system;

use app\admin\model\SystemConfig;
use app\admin\service\TriggerService;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;

/**
 * Class Config
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="系统配置管理")
 */
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
        $this->assign('upload_types', config('admin.upload_types'));
        $this->assign('editor_types', config('admin.editor_types'));
    }

    /**
     * @NodeAnnotation(title="列表")
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @NodeAnnotation(title="保存")
     */
    public function save()
    {
        $this->checkPostRequest();
        $post         = $this->request->post();
        $notAddFields = ['_token', 'file', 'group'];
        try {
            $group = $post['group'] ?? '';
            if (empty($group)) $this->error('保存失败');
            if ($group == 'upload') {
                $upload_types = config('admin.upload_types');
                // 兼容旧版本
                $this->model->where('name', 'upload_allow_type')->update(['value' => implode(',', array_keys($upload_types))]);
            }
            foreach ($post as $key => $val) {
                if (in_array($key, $notAddFields)) continue;
                if ($this->model->where('name', $key)->count()) {
                    $this->model->where('name', $key)->update(['value' => $val,]);
                } else {
                    $this->model->create(
                        [
                            'name'  => $key,
                            'value' => $val,
                            'group' => $group,
                        ]);
                }
            }
            TriggerService::updateMenu();
            TriggerService::updateSysconfig();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

}