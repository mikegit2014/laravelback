<?php namespace App\Services\Admin\ActionLog\Foundation\Acl;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use App\Models\Admin\User as UserModel;
use Request, Lang;

/**
 * 用户操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class User extends AbstractActionLog
{
    /**
     * 对用户组权限的日志记录
     */
    public function handler()
    {
        if(Request::method() !== 'POST') return false;
        if( ! $this->isLog()) return false;
        $id = Request::input('id');
        $groupInfo = (new UserModel())->getOneUserById(intval($id));
        event(new ActionLog(Lang::get('actionlog.acl_user', ['username' => $groupInfo['name']])));
    }
    
}
