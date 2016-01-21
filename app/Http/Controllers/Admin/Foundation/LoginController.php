<?php

namespace App\Http\Controllers\Admin\Foundation;

use App\Http\Controllers\Admin\Controller;
use App\Services\Admin\Login\Process as LoginProcess;
use App\Services\Admin\SC;
use App\Services\Admin\Acl\Acl;
use Request;

/**
 * 登录相关
 *
 * @author jiang <mylampblog@163.com>
 */
class LoginController extends Controller
{
    /**
     * 登录页面，如果没有登录会显示登录页面。
     *
     * @access public
     */
    public function index()
    {
        
        $isLogin = with(new LoginProcess())->getProcess()->hasLogin();
        if($isLogin) return redirect(R('common', 'foundation.index.index'));
        return response(view('admin.login.index'));
    }

    /**
     * 开始登录处理，并保存用户的权限信息
     *
     * @param App\Services\Admin\Login\Process $loginProcess 登录核心处理
     * @access public
     */
    public function getProc(LoginProcess $loginProcess, Acl $aclObj)
    {
        $username = Request::input('username');
        $password = Request::input('password');
        $callback = Request::input('callback');

        if($error = $loginProcess->getProcess()->validate($username, $password)) {
            return response()->json(['msg' => $error, 'result' => false])->setCallback($callback);
        }
        
        /*if($userInfo = $loginProcess->getProcess()->check($username, $password)) {
            SC::setUserPermissionSession($aclObj->getUserAccessPermission($userInfo));
        }*/
        $userInfo = 1;

        $success = ['msg' => '登录成功', 'result' => true, 'jumpUrl' => R('common', 'foundation.index.index')];
        $fails = ['msg' => '登录失败', 'result' => false];
        $result = $userInfo ? $success : $fails;
        
        return response()->json($result)->setCallback($callback);
    }

    /**
     * 初始化登录，返回加密密钥
     *
     * @param App\Services\Login\Process $loginProcess 登录核心处理
     * @access public
     */
    public function getPrelogin(LoginProcess $loginProcess)
    {
        $publicKey = $loginProcess->getProcess()->setPublicKey();
        $callback = Request::input('callback');
        $result = ['pKey' => $publicKey, 'a' => csrf_token()];
        return response()->json($result)->setCallback($callback);
    }

    /**
     * 登录退出
     */
    public function getOut(LoginProcess $loginProcess)
    {
        $loginProcess->getProcess()->logout();
        return redirect(url('/'));
    }

}