<?php

namespace App\Http\Controllers\Admin\Foundation;

use Request, Lang, Session;
use App\Services\Admin\SC;
use App\Libraries\Js;
use App\Services\Admin\Acl\Acl;
use App\Http\Controllers\Admin\Controller;
use App\Services\Admin\Login\Process as LoginProcess;
use App\Services\Admin\User\Validate\User as UserValidate;

/**
 * 用户相关
 *
 * @author jiang <mylampblog@163.com>
 */
class UserController extends Controller
{
    /**
     * 用户管理列表
     *
     * @access public
     */
    public function index()
    {
        Session::flashInput(['http_referer' => Request::fullUrl()]);
        $userList = app('model.admin.user')->getAllUser(['group_id' => Request::input('gid')]);
        $page = $userList->setPath('')->appends(Request::all())->render();
        $groupList = app('model.admin.group')->getAllGroup();
        return view('admin.user.index',
            compact('userList', 'groupList', 'page')
        );
    }
    
    /**
     * 增加一个用户
     *
     * @access public
     */
    public function add()
    {
        if(Request::method() == 'POST') {
            return $this->addUserInfoToDatabase();
        }

        $groupId = SC::getLoginSession()->group_id;
        $groupInfo = app('model.admin.group')->getOneGroupById($groupId);

        $isSuperSystemManager = app('admin.acl')->isSuperSystemManager();
        if($isSuperSystemManager) $groupInfo['level'] = 0;

        $groupList = app('model.admin.group')->getGroupLevelLessThenCurrentUser($groupInfo['level']);
        $formUrl = R('common', 'foundation.user.add');

        return view('admin.user.add',
            compact('groupList', 'formUrl')
        );
    }
    
    /**
     * 保存数据到数据库
     *
     * @access private
     */
    private function addUserInfoToDatabase()
    {
        $data = (array) Request::input('data');
        $data['add_time'] = time();
        app('param.admin.usersave')->setAttributes($data);
        if(app('process.admin.user')->addUser(app('param.admin.usersave'))) {
            $this->setActionLog();
            return Js::locate(R('common', 'foundation.user.index'), 'parent');
        }
        return Js::error(app('process.admin.user')->getErrorMessage());
    }
    
    /**
     * 删除用户
     *
     * @access public
     */
    public function delete()
    {
        $id = with(new UserValidate())->deleteIds((array) Request::input('id'));
        if( ! $id or ! is_array($id)) {
            return responseJson(Lang::get('common.action_error'));
        }

        $userInfos = app('model.admin.user')->getUserInIds($id);

        if(app('process.admin.user')->detele($id)) {
            $this->setActionLog(['userInfos' => $userInfos]);
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson(
            app('process.admin.user')->getErrorMessage()
        );
    }
    
    /**
     * 编辑用户的资料
     *
     * @access public
     */
    public function edit()
    {
        if(Request::method() == 'POST') {
            return $this->updateUserInfoToDatabase();
        }

        Session::flashInput(['http_referer' => Session::getOldInput('http_referer')]);

        $id = Request::input('id');
        $userId = url_param_decode($id);

        if( ! $userId or ! is_numeric($userId)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        $userInfo = app('model.admin.user')->getOneUserById($userId);
        if(empty($userInfo)) {
            return Js::error(Lang::get('user.user_not_found'), true);
        }

        if( ! app('admin.acl')->checkGroupLevelPermission($userId, Acl::GROUP_LEVEL_TYPE_USER)) {
            return Js::error(Lang::get('common.account_level_deny'), true);
        }

        $groupInfo = app('model.admin.group')->getOneGroupById(SC::getLoginSession()->group_id);
        $isSuperSystemManager = app('admin.acl')->isSuperSystemManager();

        if($isSuperSystemManager) $groupInfo['level'] = 0;

        $groupList = app('model.admin.group')->getGroupLevelLessThenCurrentUser($groupInfo['level']);
        $formUrl = R('common', 'foundation.user.edit');

        return view('admin.user.add',
            compact('userInfo', 'formUrl', 'id', 'groupList')
        );
    }
    
    /**
     * 更新用户信息到数据库
     *
     * @access private
     */
    private function updateUserInfoToDatabase()
    {
        $httpReferer = Session::getOldInput('http_referer');
        $data = (array) Request::input('data');

        app('param.admin.usersave')->setAttributes($data);

        if(app('process.admin.user')->editUser(app('param.admin.usersave'))) {
            $this->setActionLog();
            $backUrl = ( ! empty($httpReferer)) ? $httpReferer : R('common', 'foundation.user.index'); 
            return Js::locate($backUrl, 'parent');
        }
        
        return Js::error(app('process.admin.user')->getErrorMessage());
    }

    /**
     * 修改自己的密码
     */
    public function mpassword()
    {
        app('param.admin.usermp')->setOldPassword(Request::input('old_password'))
               ->setNewPassword(Request::input('new_password'))
               ->setNewPasswordRepeat(Request::input('new_password_repeat'));
               
        if(app('process.admin.user')->modifyPassword(app('param.admin.usermp'))) {
            (new LoginProcess())->getProcess()->logout();
            return responseJson(Lang::get('common.action_success'), true);
        }
        return responseJson(app('process.admin.user')->getErrorMessage());
    }
    
}