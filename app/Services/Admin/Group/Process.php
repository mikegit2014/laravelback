<?php namespace App\Services\Admin\Group;

use Lang;
use App\Models\Admin\Access as AccessModel;
use App\Services\Admin\Group\Validate\Group as GroupValidate;
use App\Services\Admin\Acl\Acl;
use App\Services\BaseProcess;

/**
 * 登录处理
 *
 * @author jiang <mylampblog@163.com>
 */
class Process extends BaseProcess
{
    /**
     * 用户组表单验证对象
     * 
     * @var object
     */
    private $groupValidate;

    /**
     * 初始化
     *
     * @access public
     */
    public function __construct()
    {
        if( ! $this->groupValidate) $this->groupValidate = new GroupValidate();
    }

    /**
     * 增加新的用户组
     *
     * @param object $data
     * @access public
     * @return boolean true|false
     */
    public function addGroup(\App\Services\Admin\Group\Param\GroupSave $data)
    {
        if( ! $this->groupValidate->add($data)) {
            return $this->setErrorMsg($this->groupValidate->getErrorMessage());
        }

        //检查当前用户的权限是否能增加这个用户组
        if( ! app('admin.acl')->checkGroupLevelPermission($data->level, Acl::GROUP_LEVEL_TYPE_LEVEL)) {
            return $this->setErrorMsg(Lang::get('common.account_level_deny'));
        }

        if(app('model.admin.group')->addGroup($data->toArray()) !== false) return true;

        return $this->setErrorMsg(Lang::get('common.action_error'));
    }

    /**
     * 删除用户组
     * 
     * @param array $ids 用户组的id
     * @access public
     * @return boolean true|false
     */
    public function detele($ids)
    {
        if( ! is_array($ids)) return false;

        foreach($ids as $key => $value)
        {
            if( ! app('admin.acl')->checkGroupLevelPermission($value, Acl::GROUP_LEVEL_TYPE_GROUP)) {
                return $this->setErrorMsg(Lang::get('common.account_level_deny'));
            }
        }

        if(app('model.admin.group')->deleteGroup($ids) !== false) {
            $result = app('model.admin.access')->deleteInfo(['type' => AccessModel::AP_GROUP, 'role_id' => $ids]);
            return true;
        }
        return $this->setErrorMsg(Lang::get('common.action_error'));
    }

    /**
     * 编辑用户组
     *
     * @param object $data
     * @access public
     * @return boolean true|false
     */
    public function editGroup(\App\Services\Admin\Group\Param\GroupSave $data)
    {
        if( ! isset($data->id)) {
            return $this->setErrorMsg(Lang::get('common.action_error'));
        }

        $id = intval(url_param_decode($data->id)); unset($data->id);

        if( ! $id) {
            return $this->setErrorMsg(Lang::get('common.illegal_operation'));
        }

        if( ! $this->groupValidate->edit($data)) {
            return $this->setErrorMsg($this->groupValidate->getErrorMessage());
        }

        //检查当前用户的权限是否能增加这个用户
        if( ! app('admin.acl')->checkGroupLevelPermission($data->level, Acl::GROUP_LEVEL_TYPE_LEVEL)) {
            return $this->setErrorMsg(Lang::get('common.account_level_deny'));
        }

        if(app('model.admin.group')->editGroup($data->toArray(), $id) !== false) return true;
        
        return $this->setErrorMsg(Lang::get('common.action_error'));
    }

}