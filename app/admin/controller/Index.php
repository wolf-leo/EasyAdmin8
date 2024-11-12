<?php

namespace app\admin\controller;

use app\admin\model\SystemAdmin;
use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use app\Request;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

class Index extends AdminController
{

    /**
     * 后台主页
     * @param Request $request
     * @return string
     */
    public function index(Request $request): string
    {
        return $this->fetch('', ['admin' => $request->adminUserInfo,]);
    }

    /**
     * 后台欢迎页
     * @return string
     * @throws Exception
     */
    public function welcome(): string
    {
        $tpVersion    = \think\facade\App::version();
        $mysqlVersion = Db::query("select version() as version")[0]['version'] ?? '未知';
        $phpVersion   = phpversion();
        $versions     = compact('tpVersion', 'mysqlVersion', 'phpVersion');
        $quick_list   = SystemQuick::field('id,title,icon,href')
            ->where(['status' => 1])->order('sort', 'desc')->limit(50)->select()->toArray();
        $quicks       = array_chunk($quick_list, 8);
        $this->assign(compact('quicks', 'versions'));
        return $this->fetch();
    }

    /**
     * 修改管理员信息
     * @param Request $request
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function editAdmin(Request $request): string
    {
        $id  = $this->adminUid;
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        empty($row) && $this->error('用户信息不存在');
        if ($request->isPost()) {
            $post = $request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [];
            $this->validate($post, $rule);
            try {
                $login_type = $post['login_type'] ?? 1;
                if ($login_type == 2) {
                    $ga_secret = (new SystemAdmin())->where('id', $id)->value('ga_secret');
                    if (empty($ga_secret)) $this->error('请先绑定谷歌验证器');
                }
                $save = $row->allowField(['head_img', 'phone', 'remark', 'update_time', 'login_type'])->save($post);
            }catch (\PDOException $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        $notes = (new SystemAdmin())->notes;
        $this->assign('notes', $notes);
        return $this->fetch();
    }

    /**
     * 修改密码
     * @param Request $request
     * @return string
     */
    public function editPassword(Request $request): string
    {
        $id  = $this->adminUid;
        $row = (new SystemAdmin())
            ->withoutField('password')
            ->find($id);
        if (!$row) {
            $this->error('用户信息不存在');
        }
        if ($request->isPost()) {
            $post = $request->post();
            $this->isDemo && $this->error('演示环境下不允许修改');
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error('两次密码输入不一致');
            }

            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
            }catch (Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                $this->success('保存成功');
            }else {
                $this->error('保存失败');
            }
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * 设置谷歌验证码
     * @param Request $request
     * @return string
     * @throws Exception
     */
    public function set2fa(Request $request): string
    {
        $id  = $this->adminUid;
        $row = (new SystemAdmin())->withoutField('password')->find($id);
        if (!$row) $this->error('用户信息不存在');
        // You can see: https://gitee.com/wolf-code/authenticator
        $ga = new \Wolfcode\Authenticator\google\PHPGangstaGoogleAuthenticator();
        if (!$request->isAjax()) {
            $old_secret = $row->ga_secret;
            $secret     = $ga->createSecret(32);
            $ga_title   = $this->isDemo ? 'EasyAdmin8演示环境' : '可自定义修改显示标题';
            $dataUri    = $ga->getQRCode($ga_title, $secret)->getDataUri();
            $this->assign(compact('row', 'dataUri', 'old_secret', 'secret'));
            return $this->fetch();
        }
        $this->isDemo && $this->error('演示环境下不允许修改');
        $post      = $request->post();
        $ga_secret = $post['ga_secret'] ?? '';
        $ga_code   = $post['ga_code'] ?? '';
        if (empty($ga_code)) $this->error('请输入验证码');
        if (!$ga->verifyCode($ga_secret, $ga_code)) $this->error('验证码错误');
        $row->ga_secret  = $ga_secret;
        $row->login_type = 2;
        $row->save();
        $this->success('操作成功');
    }

}
