<?php

namespace app\admin\controller\mall;

use app\admin\model\MallCate;
use app\admin\model\MallGoods;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\db\exception\DbException;
use think\response\Json;

/**
 * Class Goods
 * @package app\admin\controller\mall
 * @ControllerAnnotation(title="商城商品管理")
 */
class Goods extends AdminController
{

    protected bool $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new MallGoods();
        $this->assign('cate', (new MallCate())->column('title', 'id'));
    }

    /**
     * @NodeAnnotation(title="列表")
     * @throws DbException
     */
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParams();
            $count = $this->model
                ->withJoin('cate', 'LEFT')
                ->where($where)
                ->count();
            $list  = $this->model
                ->withJoin('cate', 'LEFT')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()->toArray();
            $data  = [
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
     * @NodeAnnotation(title="入库")
     */
    public function stock(Request $request, $id): string
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $post['total_stock'] = $row->total_stock + $post['stock'];
                $post['stock']       = $row->stock + $post['stock'];
                $save                = $row->save($post);
            }catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

}