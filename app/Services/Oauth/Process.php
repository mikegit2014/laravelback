<?php namespace App\Services\Oauth;

use App\Services\Oauth\SC;

class Process
{
    public function checkLogin()
    {
        $userInfo = SC::getLoginSession();
        return $userInfo;
    }

    /**
     * 登录退出
     *
     * @return void
     */
    public function logout()
    {
        return SC::delLoginSession();
    }
}