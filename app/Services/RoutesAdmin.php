<?php

Route::group(['middleware' => ['csrf']], function() {
    Route::get('/', 'Admin\Foundation\LoginController@index');
    Route::controller('login', 'Admin\Foundation\LoginController', ['getOut' => 'foundation.login.out']);
});

Route::group(['middleware' => ['auth', 'acl', 'alog']], function()
{
    Route::any('{module}-{class}-{action}.html', ['as' => 'common', function($module, $class, $action)
    {
        $touchClass = 'App\\Http\\Controllers\\Admin\\'.ucfirst(strtolower($module)).'\\'.ucfirst(strtolower($class)).'Controller';
        if(class_exists($touchClass))
        {
            $classObject = new $touchClass();
            if(method_exists($classObject, $action)) return call_user_func(array($classObject, $action));
        }
        return abort(404);
    }])->where(['module' => '[0-9a-z]+', 'class' => '[0-9a-z]+', 'action' => '[0-9a-z]+']);
});