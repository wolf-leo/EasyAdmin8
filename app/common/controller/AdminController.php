<?php

namespace app\common\controller;

use app\admin\service\ConfigService;
use app\admin\traits\Curd;
use app\BaseController;
use app\common\constants\AdminConstant;
use app\common\traits\JumpTrait;
use think\facade\View;
use think\helper\Str;
use think\response\Json;

class AdminController extends BaseController
{
    use Curd;
    use JumpTrait;

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
     * @var int|string
     */
    protected int|string $adminUid;


    /**
     * 初始化方法
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->adminUid = request()->adminUserInfo['id'] ?? 0;
        $this->isDemo   = env('EASYADMIN.IS_DEMO', false);
        $this->setOrder();
        $this->viewInit();
    }

    /**
     * 初始化排序
     * @return $this
     */
    public function setOrder(): static
    {
        $tableOrder = $this->request->param('tableOrder/s', '');
        if (!empty($tableOrder)) {
            [$orderField, $orderType] = explode(' ', $tableOrder);
            $this->sort = [$orderField => $orderType];
        }
        return $this;
    }

    /**
     * 模板变量赋值
     * @param array|string $name 模板变量
     * @param mixed|null $value 变量值
     */
    public function assign(array|string $name, mixed $value = null): void
    {
        View::assign($name, $value);
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @param bool $layout 是否需要自动布局
     * @return string
     */
    public function fetch(string $template = '', array $vars = [], bool $layout = true): string
    {
        if ($layout) View::instance()->engine()->layout('/layout/default');
        View::assign($vars);
        return View::fetch($template);
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
            $op = !empty($ops[$key]) ? $ops[$key] : '%*%';
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
        $data   = $this->model->where($this->selectWhere)->field($fields)->select()->toArray();
        $this->success(null, $data);
    }

    /**
     * 初始化视图参数
     */
    private function viewInit(): void
    {
        $request = app()->request;
        list($thisModule, $thisController, $thisAction) = [app('http')->getName(), app()->request->controller(), $request->action()];
        list($thisControllerArr, $jsPath) = [explode('.', $thisController), null];
        foreach ($thisControllerArr as $vo) {
            empty($jsPath) ? $jsPath = parse_name($vo) : $jsPath .= '/' . parse_name($vo);
        }
        $autoloadJs           = file_exists(root_path('public') . "static/{$thisModule}/js/{$jsPath}.js");
        $thisControllerJsPath = "{$thisModule}/js/{$jsPath}.js";
        $adminModuleName      = config('admin.alias_name');
        $isSuperAdmin         = $this->adminUid == AdminConstant::SUPER_ADMIN_ID;
        $data                 = [
            'isDemo'               => $this->isDemo,
            'adminModuleName'      => $adminModuleName,
            'thisController'       => parse_name($thisController),
            'thisAction'           => $thisAction,
            'thisRequest'          => parse_name("{$thisModule}/{$thisController}/{$thisAction}"),
            'thisControllerJsPath' => "{$thisControllerJsPath}",
            'autoloadJs'           => $autoloadJs,
            'isSuperAdmin'         => $isSuperAdmin,
            'version'              => env('APP_DEBUG') ? time() : ConfigService::getVersion(),
            'adminUploadUrl'       => url('ajax/upload', [], false),
            'adminEditor'          => sysConfig('site', 'editor_type') ?: 'wangEditor',
        ];
        View::assign($data);
    }


    /**
     * 严格校验接口是否为POST请求
     */
    protected function checkPostRequest(): void
    {
        if (!$this->request->isPost()) {
            $this->error("当前请求不合法！");
        }
    }

}