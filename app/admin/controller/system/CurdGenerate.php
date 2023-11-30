<?php

namespace app\admin\controller\system;

use app\admin\service\curd\BuildCurd;
use app\admin\service\curd\exceptions\TableException;
use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\db\exception\PDOException;
use think\exception\FileException;
use think\facade\Db;
use think\helper\Str;
use think\response\Json;

/**
 * @ControllerAnnotation(title="CURD可视化管理")
 * @package app\admin\controller\system
 */
class CurdGenerate extends AdminController
{
    /**
     * @NodeAnnotation(title="列表")
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @NodeAnnotation(title="操作")
     * @throws TableException
     */
    public function save(Request $request, string $type = ''): ?Json
    {
        if (!$request->isAjax()) return $this->error();
        $tb_prefix = $request->param('tb_prefix/s', '');
        $tb_name   = $request->param('tb_name/s', '');
        if (empty($tb_name) || empty($tb_prefix)) return $this->error('参数错误');
        switch ($type) {
            case "search":
                try {
                    $list = Db::query("SHOW FULL COLUMNS FROM {$tb_prefix}{$tb_name}");
                    $data = [];
                    foreach ($list as $value) {
                        $data[] = [
                            'name'  => $value['Field'],
                            'type'  => $value['Type'],
                            'key'   => $value['Key'],
                            'extra' => $value['Extra'],
                            'null'  => $value['Null'],
                            'desc'  => $value['Comment'],
                        ];
                    }
                    return $this->success('查询成功', compact('data', 'list'));
                } catch (PDOException $exception) {
                    return $this->error($exception->getMessage());
                }
                break;
            case "add":
                $force = $request->post('force/d', 0);
                try {
                    $build = (new BuildCurd())->setTablePrefix($tb_prefix)->setTable($tb_name);
                    $build->setForce($force); // 强制覆盖
                    $build    = $build->render();
                    $fileList = $build->getFileList();
                    if (empty($fileList)) return $this->error('这里什么都没有');
                    $result = $build->create();
                    $_file  = $result[0] ?? '';
                    $link   = '';
                    if (!empty($_file)) {
                        $_fileExp      = explode(DIRECTORY_SEPARATOR, $_file);
                        $_fileExp_last = array_slice($_fileExp, -2);
                        $link          = '/' . env('EASYADMIN.ADMIN', 'admin') . '/' . $_fileExp_last[0] . '.' . Str::snake(explode('.php', end($_fileExp_last))[0] ?? '') . '/index';
                    }
                    return $this->success('生成成功', compact('result', 'link'));
                } catch (FileException $exception) {
                    return json(['code' => -1, 'msg' => $exception->getMessage()]);
                }
                break;
            case "delete":
                try {
                    $build    = (new BuildCurd())->setTablePrefix($tb_prefix)->setTable($tb_name);
                    $build    = $build->render();
                    $fileList = $build->getFileList();
                    if (empty($fileList)) return $this->error('这里什么都没有');
                    $result = $build->delete();
                    return $this->success('删除自动生成CURD文件成功', compact('result'));
                } catch (FileException $exception) {
                    return json(['code' => -1, 'msg' => $exception->getMessage()]);
                }
                break;
            default:
                return $this->error('参数错误');
                break;
        }
    }
}