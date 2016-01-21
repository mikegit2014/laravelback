<?php namespace App\Services\Admin\Login;

use App\Models\Admin\User as UserModel;
use App\Models\Admin\Permission as PermissionModel;
use App\Services\Admin\SC;
use App\Services\Admin\Login\AbstractProcess;
use Validator, Lang;
use Request, Session;

/**
 * 登录处理
 *
 * @author jiang <mylampblog@163.com>
 */
class ProcessDefault extends AbstractProcess
{
    /**
     * 用户模型
     * 
     * @var object
     */
    private $userModel;

    /**
     * 权限模型
     * 
     * @var object
     */
    private $permissionModel;

    /**
     * 初始化
     *
     * @access public
     */
    public function __construct()
    {
        if( ! $this->userModel) $this->userModel = new UserModel();
        if( ! $this->permissionModel) $this->permissionModel = new PermissionModel();
    }

    /**
     * 登录验证
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @access public
     * @return boolean false|用户的信息
     */
    public function check($username, $password)
    {
        $userInfo = $this->userModel->InfoByName($username);
        $sign = md5($userInfo['password'].$this->getPublicKey());
        $this->delPublicKey();
        if($sign != strtolower($password)) return false;

        //更新最后登陆状态
        $data['last_login_time'] = time();
        $data['last_login_ip'] = Request::ip();
        $this->userModel->updateLastLoginInfo($userInfo->id, $data);

        //设置一些session待用
        SC::setLoginSession($userInfo);
        SC::setUserCurrentTime();
        SC::setAllPermissionSession($this->permissionModel->getAllAccessPermission());

        //记录日志
        $log = new \App\Events\Admin\ActionLog(Lang::get('login.login_sys'), ['userInfo' => $userInfo]);
        event($log);

        return $userInfo;
    }

    /**
     * 检测post过来的数据
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @access public
     * @return false|string
     */
    public function validate($username, $password)
    {
        $this->checkCsrfToken();
        $data = array( 'username' => $username, 'password' => $password );
        $rules = array( 'username' => 'required|min:1', 'password' => 'required|min:1' );
        $messages = array(
            'username.required' => Lang::get('login.please_input_username'),
            'username.min' => Lang::get('login.please_input_username'),
            'password.required' => Lang::get('login.please_input_password'),
            'password.min' => Lang::get('login.please_input_password')
        );
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails())
        {
            return $validator->messages()->first();
        }
        return false;
    }

    /**
     * 判断是否已经登录
     *
     * @return boolean true|false
     */
    public function hasLogin()
    {
        $hasLogin = SC::getLoginSession();
        
        return $hasLogin && $this->checkLeftTime();
    }

    /**
     * 判断用户多久没有操作了，是否需要退出
     * 
     * @return boolean
     */
    private function checkLeftTime()
    {
        $userTime = SC::getUserCurrentTime();
        $now = time();
        if($now - $userTime > config('sys.sys_session_lefttime'))
        {
            return false;
        }
        return true;
    }

    /**
     * 手动的验证csrftoken
     */
    private function checkCsrfToken()
    {
        $csrf = new \App\Services\CsrfValidate();
        $csrf->tokensMatch();
    }

    /**
     * 设置并返回加密密钥
     *
     * @return string 密钥
     */
    public function setPublicKey()
    {
        return SC::setPublicKey();
    }

    /**
     * 取得刚才设置的加密密钥
     * 
     * @return string 密钥
     */
    public function getPublicKey()
    {
        return SC::getPublicKey();
    }

    /**
     * 删除密钥
     * 
     * @return void
     */
    public function delPublicKey()
    {
        return SC::delPublicKey();
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