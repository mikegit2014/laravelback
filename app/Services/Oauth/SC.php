<?php namespace App\Services\Oauth;

use Session;

class SC {

    /**
     * oauth_stat
     */
    CONST OAUTH_STAT = 'OAUTH_STAT';

    /**
     * oauth_stat
     */
    CONST OAUTH_LOGIN_SESSION = 'OAUTH_LOGIN_SESSION';

    /**
     * set oauth stat
     */
    static public function setOauthStat($stat)
    {
        return Session::put(self::OAUTH_STAT, $stat);
    }

    /**
     * get oauth stat
     */
    static public function getOauthStat()
    {
        return Session::get(self::OAUTH_STAT);
    }

    /**
     * del oauth session
     */
    static public function delOauthStat()
    {
        return Session::forget(self::OAUTH_STAT);
    }

    /**
     * set oauth login session
     */
    static public function setLoginSession($userInfo)
    {
        return Session::put(self::OAUTH_LOGIN_SESSION, $userInfo);
    }

    /**
     * get oauth login session
     */
    static public function getLoginSession()
    {
        return Session::get(self::OAUTH_LOGIN_SESSION);
    }

    /**
     * 删除登录的session
     * 
     * @return void
     */
    static public function delLoginSession()
    {
        Session::forget(self::OAUTH_LOGIN_SESSION);
        Session::flush();
        Session::regenerate();
    }

}