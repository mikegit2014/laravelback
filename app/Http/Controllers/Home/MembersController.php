<?php namespace App\Http\Controllers\Home;


/**
 * 用户相关的，包括注册，登陆。
 *
 * @author jiang <mylampblog@163.com>
 */
class MembersController extends Controller
{
    /**
     * oauth client provider
     * 
     * @var object
     */
    private $provider;

    /**
     * 初始化
     */
    public function __construct()
    {
        
    }

    /**
     * 用户的页面
     */
    public function login()
    {
        echo 'login';
    }

    /**
     * 登录退出
     */
    public function logout()
    {
       echo 'logout';
    }

    /**
     * 登陆的回调地址
     */
    public function loginback()
    {
        echo 'back';
    }

    /**
     * 注册
     */
    public function reg()
    {
        echo 'reg';
    }

}