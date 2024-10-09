<?php

namespace app\admin\controller\system;

use app\admin\model\SystemLog;
use app\admin\service\tool\CommonTool;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use jianyan\excel\Excel;
use think\App;
use think\db\exception\DbException;
use think\db\exception\PDOException;
use think\facade\Db;
use think\response\Json;

/**
 * @ControllerAnnotation(title="操作日志管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class Log extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemLog();
    }

    /**
     * @NodeAnnotation(title="列表")
     */
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            [$page, $limit, $where, $excludeFields] = $this->buildTableParams(['month']);
            $month = !empty($excludeFields['month']) ? date('Ym', strtotime($excludeFields['month'])) : date('Ym');
            $model = $this->model->setMonth($month)->with('admin')->where($where);
            try {
                $count = $model->count();
                $list  = $model->page($page, $limit)->order($this->sort)->select();
            }catch (PDOException|DbException $exception) {
                $count = 0;
                $list  = [];
            }
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnnotation(title="导出")
     */
    public function export(): bool
    {
        if (env('EASYADMIN.IS_DEMO', false)) {
            $this->error('演示环境下不允许操作');
        }
        [$page, $limit, $where, $excludeFields] = $this->buildTableParams(['month']);
        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
        $prefix    = config('database.connections.mysql.prefix');
        $dbList    = Db::query("show full columns from {$prefix}{$tableName}");
        $header    = [];
        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }
        $month = !empty($excludeFields['month']) ? date('Ym', strtotime($excludeFields['month'])) : date('Ym');
        $model = $this->model->setMonth($month)->with('admin')->where($where);
        try {
            $list = $model
                ->where($where)
                ->limit(100000)
                ->order('id', 'desc')
                ->select()
                ->toArray();
        }catch (PDOException|DbException $exception) {
            $this->error($exception->getMessage());
        }
        $fileName = time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }

    /**
     * @NodeAnnotation(title="框架日志")
     */
    public function record(): string
    {
        return (new \Wolfcode\PhpLogviewer\thinkphp\LogViewer())->fetch();
    }

}