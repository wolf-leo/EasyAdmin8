<?php

namespace app\common\controller;

use app\admin\service\ConfigService;
use app\BaseController;
use app\common\constants\AdminConstant;
use app\common\service\AuthService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Env;
use think\facade\View;
use think\helper\Str;
use think\response\Json;

/**
 * Class AdminController
 * @package app\common\controller
 */
class AdminController extends BaseController
{

    use \app\common\traits\JumpTrait;

    /**
     * 当前模型
     * @Model
     * @var object
     */
    protected object $model;

    /**
     * 字段排序
     * @var array
     */
    protected array $sort = [
        'id' => 'desc',
    ];

    /**
     * 允许修改的字段
     * @var array
     */
    protected array $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
    ];

    /**
     * 不导出的字段信息
     * @var array
     */
    protected array $noExportFields = ['delete_time', 'update_time'];

    /**
     * 下拉选择条件
     * @var array
     */
    protected array $selectWhere = [];

    /**
     * 是否关联查询
     * @var bool
     */
    protected bool $relationSearch = false;

    /**
     * 模板布局, false取消
     * @var string|bool
     */
    protected string|bool $layout = 'layout/default';

    /**
     * 是否为演示环境
     * @var bool
     */
    protected bool $isDemo = false;


    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $this->layout && $this->app->view->engine()->layout($this->layout);
        $this->isDemo = Env::get('EASYADMIN.IS_DEMO', false);
        $this->viewInit();
        $this->checkAuth();
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return mixed
     */
    public function assign($name, $value = null): mixed
    {
        return $this->app->view->assign($name, $value);
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @return mixed
     */
    public function fetch(string $template = '', array $vars = []): mixed
    {
        return $this->app->view->fetch($template, $vars);
    }

    /**
     * 重写验证规则
     * @param array $data
     * @param array|string $validate
     * @param array $message
     * @param bool $batch
     * @return bool
     */
    public function validate(array $data, $validate, array $message = [], bool $batch = false): bool
    {
        try {
            parent::validate($data, $validate, $message, $batch);
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return true;
    }

    /**
     * 构建请求参数
     * @param array $excludeFields 忽略构建搜索的字段
     * @return array
     */
    protected function buildTableParams(array $excludeFields = []): array
    {
        $get     = $this->request->get();
        $page    = !empty($get['page']) ? $get['page'] : 1;
        $limit   = !empty($get['limit']) ? $get['limit'] : 15;
        $filters = !empty($get['filter']) ? htmlspecialchars_decode($get['filter']) : '{}';
        $ops     = !empty($get['op']) ? htmlspecialchars_decode($get['op']) : '{}';
        // json转数组
        $filters  = json_decode($filters, true);
        $ops      = json_decode($ops, true);
        $where    = [];
        $excludes = [];
        // 判断是否关联查询
        $tableName = Str::snake(lcfirst($this->model->getName()));
        foreach ($filters as $key => $val) {
            if (in_array($key, $excludeFields)) {
                $excludes[$key] = $val;
                continue;
            }
            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
            }

            switch (strtolower($op)) {
                case '=':
                    $where[] = [$key, '=', $val];
                    break;
                case '%*%':
                    $where[] = [$key, 'LIKE', "%{$val}%"];
                    break;
                case '*%':
                    $where[] = [$key, 'LIKE', "{$val}%"];
                    break;
                case '%*':
                    $where[] = [$key, 'LIKE', "%{$val}"];
                    break;
                case 'range':
                    [$beginTime, $endTime] = explode(' - ', $val);
                    $where[] = [$key, '>=', strtotime($beginTime)];
                    $where[] = [$key, '<=', strtotime($endTime)];
                    break;
                default:
                    $where[] = [$key, $op, "%{$val}"];
            }
        }
        return [$page, $limit, $where, $excludes];
    }

    /**
     * 下拉选择列表
     * @return Json
     */
    public function selectList(): Json
    {
        $fields = input('selectFields');
        $data   = $this->model
            ->where($this->selectWhere)
            ->field($fields)
            ->select();
        $this->success(null, $data);
    }

    /**
     * 初始化视图参数
     */
    private function viewInit()
    {
        $request = app()->request;
        list($thisModule, $thisController, $thisAction) = [app('http')->getName(), app()->request->controller(), $request->action()];
        list($thisControllerArr, $jsPath) = [explode('.', $thisController), null];
        foreach ($thisControllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }
        $autoloadJs           = file_exists(root_path('public') . "static/{$thisModule}/js/{$jsPath}.js");
        $thisControllerJsPath = "{$thisModule}/js/{$jsPath}.js";
        $adminModuleName      = config('app.admin_alias_name');
        $isSuperAdmin         = session('admin.id') == AdminConstant::SUPER_ADMIN_ID;
        $data                 = [
            'adminModuleName'      => $adminModuleName,
            'thisController'       => parse_name($thisController),
            'thisAction'           => $thisAction,
            'thisRequest'          => parse_name("{$thisModule}/{$thisController}/{$thisAction}"),
            'thisControllerJsPath' => "{$thisControllerJsPath}",
            'autoloadJs'           => $autoloadJs,
            'isSuperAdmin'         => $isSuperAdmin,
            'version'              => env('APP_DEBUG') ? time() : ConfigService::getVersion(),
            'adminUploadUrl'       => url('ajax/upload', [], false),
            'adminEditor'          => sysconfig('site', 'editor_type') ?: 'ueditor',
        ];

        View::assign($data);
    }

    /**
     * 检测权限
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function checkAuth()
    {
        $adminConfig = config('admin');
        $adminId     = session('admin.id');
        $expireTime  = session('admin.expire_time');
        /** @var AuthService $authService */
        $authService       = app(AuthService::class, ['adminId' => $adminId]);
        $currentNode       = $authService->getCurrentNode();
        $currentController = parse_name(app()->request->controller());

        // 验证登录
        if (
            !in_array($currentController, $adminConfig['no_login_controller'])
            && !in_array($currentNode, $adminConfig['no_login_node'])) {
            empty($adminId) && $this->error('请先登录后台', [], __url('admin/login/index'));

            // 判断是否登录过期
            if ($expireTime !== true && time() > $expireTime) {
                session('admin', null);
                $this->error('登录已过期，请重新登录', [], __url('admin/login/index'));
            }
        }

        // 验证权限
        if (
            !in_array($currentController, $adminConfig['no_auth_controller'])
            && !in_array($currentNode, $adminConfig['no_auth_node'])) {
            $check = $authService->checkNode($currentNode);
            !$check && $this->error('无权限访问');

            // 判断是否为演示环境
            if (env('EASYADMIN.IS_DEMO', false) && app()->request->isPost()) {
                $this->error('演示环境下不允许修改');
            }

        }
    }

    /**
     * 严格校验接口是否为POST请求
     */
    protected function checkPostRequest()
    {
        if (!$this->request->isPost()) {
            $this->error("当前请求不合法！");
        }
    }

}