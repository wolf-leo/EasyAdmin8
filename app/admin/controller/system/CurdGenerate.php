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
use think\facade\Console;
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
    public function index(Request $request): Json|string
    {
        return $this->fetch();
    }

    /**
     * @NodeAnnotation(title="操作")
     * @throws TableException
     */
    public function save(Request $request, string $type = ''): ?Json
    {
        if (!$request->isAjax()) $this->error();
        switch ($type) {
            case "search":
                $tb_prefix = $request->param('tb_prefix/s', '');
                $tb_name   = $request->param('tb_name/s', '');
                if (empty($tb_name)) $this->error('参数错误');

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
                    $this->success('查询成功', compact('data', 'list'));
                }catch (PDOException $exception) {
                    $this->error($exception->getMessage());
                }
                break;
            case "add":
                $tb_prefix = $request->param('tb_prefix/s', '');
                $tb_name   = $request->param('tb_name/s', '');
                if (empty($tb_name)) $this->error('参数错误');

                $tb_fields = $request->param('tb_fields');
                $force     = $request->post('force/d', 0);
                try {
                    $build = (new BuildCurd())->setTablePrefix($tb_prefix)->setTable($tb_name);
                    $build->setForce($force); // 强制覆盖
                    // 新增字段类型
                    if ($tb_fields) {
                        foreach ($tb_fields as $tk => $tf) {
                            if (empty($tf)) continue;
                            $tf = array_values($tf);
                            switch ($tk) {
                                case 'ignore':
                                    $build->setIgnoreFields($tf, true);
                                    break;
                                case 'select':
                                    $build->setSelectFields($tf, true);
                                    break;
                                case 'radio':
                                    $build->setRadioFieldSuffix($tf, true);
                                    break;
                                case 'checkbox':
                                    $build->setCheckboxFieldSuffix($tf, true);
                                    break;
                                case 'image':
                                    $build->setImageFieldSuffix($tf, true);
                                    break;
                                case 'images':
                                    $build->setImagesFieldSuffix($tf, true);
                                    break;
                                case 'date':
                                    $build->setDateFieldSuffix($tf, true);
                                    break;
                                case 'datetime':
                                    $build->setDatetimeFieldSuffix($tf, true);
                                    break;
                                case 'editor':
                                    $build->setEditorFields($tf, true);
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                    $build    = $build->render();
                    $fileList = $build->getFileList();
                    if (empty($fileList)) $this->error('这里什么都没有');
                    $result = $build->create();
                    $_file  = $result[0] ?? '';
                    $link   = '';
                    if (!empty($_file)) {
                        $_fileExp        = explode(DIRECTORY_SEPARATOR, $_file);
                        $_fileExp_last   = array_slice($_fileExp, -2);
                        $_fileExp_last_0 = $_fileExp_last[0] . '.';
                        if ($_fileExp_last[0] == 'controller') $_fileExp_last_0 = '';
                        $link = '/' . config('admin.alias_name') . '/' . $_fileExp_last_0 . Str::snake(explode('.php', end($_fileExp_last))[0] ?? '') . '/index';
                    }
                    $this->success('生成成功', compact('result', 'link'));
                }catch (FileException $exception) {
                    return json(['code' => -1, 'msg' => $exception->getMessage()]);
                }
                break;
            case "delete":
                $tb_prefix = $request->param('tb_prefix/s', '');
                $tb_name   = $request->param('tb_name/s', '');
                if (empty($tb_name)) $this->error('参数错误');

                try {
                    $build    = (new BuildCurd())->setTablePrefix($tb_prefix)->setTable($tb_name);
                    $build    = $build->render();
                    $fileList = $build->getFileList();
                    if (empty($fileList)) $this->error('这里什么都没有');
                    $result = $build->delete();
                    $this->success('删除自动生成CURD文件成功', compact('result'));
                }catch (FileException $exception) {
                    return json(['code' => -1, 'msg' => $exception->getMessage()]);
                }
                break;
            case 'console':
                $command = $request->post('command', '');
                if (empty($command)) $this->error('请输入命令');
                $commandExp = explode(' ', $command);
                try {

                    $output = Console::call('curd', [...$commandExp]);
                }catch (\Throwable $exception) {
                    $this->error($exception->getMessage() . $exception->getLine());
                }
                if (empty($output)) $this->error('设置错误');
                $this->success($output->fetch());
                break;
            default:
                $this->error('参数错误');
                break;
        }
    }
}