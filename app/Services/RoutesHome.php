<?php

/**
 * 这里主要是写首页相关的路由
 *
 * @author jiang <mylampblog@163.com>
 */


Route::get('/', ['as' => 'blog.index.index', 'uses' => 'Home\IndexController@index']);

Route::get('/index/detail/{id}.html', ['as' => 'blog.index.detail', 'uses' => 'Home\IndexController@detail'])->where('id', '[0-9]+');


//oauth login
Route::get('/login.html', ['as' => 'blog.login', 'uses' => 'Home\MembersController@login']);
Route::get('/reg.html', ['as' => 'blog.reg', 'uses' => 'Home\MembersController@reg']);
Route::get('/login_back.html', ['as' => 'blog.login.back', 'uses' => 'Home\MembersController@loginback']);
Route::get('/login_out.html', ['as' => 'blog.login.out', 'uses' => 'Home\MembersController@logout']);

// common routes
Route::any('{class}/{action}.html', ['as' => 'home', function($class, $action) {
    $touchClass = 'App\\Http\\Controllers\\Home\\'.ucfirst(strtolower($class)).'Controller';
    $classObject = new $touchClass();
    if( ! class_exists($touchClass) or ! method_exists($classObject, $action) ) {
        return abort(404);
    }
    return header_cache(call_user_func(array($classObject, $action)), false);
}])->where(['class' => '[0-9a-z]+', 'action' => '[0-9a-z]+']);