<?php

namespace App\Services;

use Route;

/**
 * 系统路由
 * 
 * 注：大部分的路由及控制器所执行的动作来说，
 * 
 * 你需要返回完整的 Illuminate\Http\Response 实例或是一个视图
 *
 * @author jiang <mylampblog@163.com>
 */
class Routes
{
    /**
     * 后台域名
     * 
     * @var string
     */
    private $adminDomain;

    /**
     * 博客域名
     * 
     * @var string
     */
    private $wwwDomain;

    /**
     * 后台域名，没有前缀
     * 
     * @var string
     */
    private $noPreDomain;
    /**
     * 初始化，取得配置
     *
     * @access public
     */
    public function __construct()
    {
        $this->adminDomain = config('sys.sys_admin_domain');
        $this->wwwDomain = config('sys.sys_blog_domain');
        $this->noPreDomain = config('sys.sys_blog_nopre_domain');
    }

    /**
     * 后台的通用路由
     * 
     * 覆盖通用的路由一定要带上别名，且別名的值为module.class.action
     * 
     * 即我们使用别名传入了当前请求所属的module,controller和action
     *
     * <code>
     *     Route::get('index-index.html', ['as' => 'module.class.action', 'uses' => 'Admin\IndexController@index']);
     * </code>
     *
     * @access public
     */
    public function admin()
    {
        /*Route::group(['domain' => $this->adminDomain], function() {
            require __DIR__ . '/RoutesAdmin.php';
        });
        return $this;*/
        Route::group(['domain' => $this->wwwDomain], function()
        {
            //重点是增加以下这个group
            Route::group(['prefix' => 'admin'], function()
            {
                require __DIR__ . '/RoutesAdmin.php';
            });
        });
        return $this;
    }

    /**
     * 博客通用路由
     * 
     * 这里必须要返回一个Illuminate\Http\Response 实例而非一个视图
     * 
     * 原因是因为csrf中需要响应的必须为一个response
     *
     * @access public
     */
    public function www()
    {
        Route::group(['domain' => $this->wwwDomain, 'middleware' => ['csrf']], function() {
            require __DIR__ . '/RoutesHome.php';
        });
        return $this;
    }

    /**
     * 博客通用路由
     * 
     * 这里必须要返回一个Illuminate\Http\Response 实例而非一个视图
     * 
     * 原因是因为csrf中需要响应的必须为一个response
     *
     * @access public
     */
    public function ewww()
    {
        Route::group(['domain' => $this->noPreDomain, 'middleware' => ['csrf']], function() {
            Route::get('/', ['as' => 'blog.index.index', 'uses' => 'Home\IndexController@index']);
        });
        return $this;
    }
}
