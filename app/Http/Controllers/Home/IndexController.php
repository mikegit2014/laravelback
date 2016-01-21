<?php namespace App\Http\Controllers\Home;

/**
 * 博客首页
 *
 * @author jiang <mylampblog@163.com>
 */
class IndexController extends Controller
{
    /**
     * 博客首页
     */
    public function index()
    {
        return view('home.index.index');
    }

    /**
     * 文章内页
     */
    public function detail()
    {
        return view('home.index.detail');
    }

}