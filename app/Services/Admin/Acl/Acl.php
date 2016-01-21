<?php

/**
 * 用户权限处理相关
 *
 * @author jiang <mylampblog@163.com>
 */

namespace App\Services\Admin\Acl;

use Config;
use App\Services\Admin\SC;

class Acl
{
    /**
     * 权限角色对应表中为用户的数据标识，这里2代表的是用户
     *
     * @var const intval
     */
    CONST AP_USER = 2;
    
    /**
     * 权限角色对应表中为用户组的数据标识，这里1代表的是用户组
     *
     * @var const intval
     */
    CONST AP_GROUP = 1;
    
    /**
     * 超级用户的role_id值
     *
     * @var const intval
     */
    CONST ADMIN_ROLE_ID = 1;

    /**
     * 创始人帐号
     *
     * @var string
     */
    CONST ADMIN_NAME = 'admin';

    /**
     * 创始人ID
     *
     * @var int
     */
    CONST ADMIN_ID = 1;

    /**
     * 检测当前用户的用户组的等级是否比其它用户或用户组的高，如果低于，则不能操作
     * 这个值就是这种情况的类型
     *
     * @var string
     */
    CONST GROUP_LEVEL_TYPE_LEVEL = 'level';

    /**
     * 检测当前用户的用户组的等级是否比其它用户或用户组的高，如果低于，则不能操作
     * 这个值就是这种情况的类型
     *
     * @var string
     */
    CONST GROUP_LEVEL_TYPE_USER = 'user';

    /**
     * 检测当前用户的用户组的等级是否比其它用户或用户组的高，如果低于，则不能操作
     * 这个值就是这种情况的类型
     *
     * @var string
     */
    CONST GROUP_LEVEL_TYPE_GROUP = 'group';

    /**
     * 取得当前登录的用户的权限角色对应表中的信息。
     *
     * @param object $userObj
     * @param int $userOrGroup
     * @access public
     * @return array|null
     */
    public function getUserAccessPermission($userObj, $userOrGroup = false)
    {
        //如果是超级管理员或者创始人登陆，那么返回所有的权限
        if($userObj->group_id == self::ADMIN_ROLE_ID or $userObj->id == self::ADMIN_ID)
        {
            return app('model.admin.permission')->getAllAccessPermission();
        }
        
        //如果需要对比用户和用户组的权限或者返回用户的权限
        if($userOrGroup == self::AP_USER or ! $userOrGroup)
        {
            $userAccessPermissionInfo = app('model.admin.access')->getUserAccessPermission($userObj->id);
        }
        
        //如果用户的权限数据为空或者指定了需要查询的权限的类型为用户组，或者需要对比用户和用户组的权限
        if($userOrGroup == self::AP_GROUP or ! $userAccessPermissionInfo or ! $userOrGroup)
        {
            $groupAccessPermissionInfo = app('model.admin.access')->getGroupAccessPermission($userObj->group_id);
        }
        
        //根据条件返回权限信息，注意的是用户的权限会覆盖用户组的权限
        return ($userOrGroup == self::AP_USER ? $userAccessPermissionInfo :
                    ($userOrGroup == self::AP_GROUP ? $groupAccessPermissionInfo :
                        ($userAccessPermissionInfo ? $userAccessPermissionInfo : $groupAccessPermissionInfo)
                    )
                );
    }

    /**
     * 检测当前用户的用户组的等级是否比其它用户或用户组的高，如果低于，则不能操作。
     * 该函数只要用于用户列表和用户组列表的相关操作。
     * 
     * @param  intval $id   用户或用户组的ID
     * @param  string $type 标识传进来的ID是用户ID('user')还是用户组ID('group'),还是level值(level)
     * @return boolean
     */
    public function checkGroupLevelPermission($id, $type)
    {
        if( ! $id) return false;

        //如果是超级用户，那么直接返回true
        if($this->isSuperSystemManager()) return true;

        //当前登陆用户的信息
        $userObj = SC::getLoginSession();

        //当前登陆用户的用户组信息
        $currentGroupInfo = app('model.admin.group')->getOneGroupById($userObj->group_id);
        if(empty($currentGroupInfo)) return false;

        //通过用户组的level来做判断
        if($type === self::GROUP_LEVEL_TYPE_LEVEL)
        {
            return ($id <= $currentGroupInfo['level']) ? false : true;
        }

        //通过用户来做判断
        if($type === self::GROUP_LEVEL_TYPE_USER)
        {
            $userInfo = app('model.admin.user')->getOneUserById($id);
            if($userInfo['name'] == self::ADMIN_NAME) return false;
            $toGroupInfo = app('model.admin.group')->getOneGroupById($userInfo['group_id']);
        }

        //通过用户组来做判断
        if($type === self::GROUP_LEVEL_TYPE_GROUP)
        {
            $toGroupInfo = app('model.admin.group')->getOneGroupById($id);
        }

        //开始判断他们的level情况
        if(isset($toGroupInfo) and $toGroupInfo['level'] <= $currentGroupInfo['level'])
        {
            return false;
        }
        return true;
    }

    /**
     * 判断当前操作是不是有权限
     * 
     * @param  string  $module   模块
     * @param  string  $class    类
     * @param  string  $function 函数
     * @return boolean           是｜否
     */
    public function checkIfHasPermission($module, $class, $function)
    {
        $module = (string) $module; $class = (string) $class; $function = (string) $function;

        //判断是不是超级用户
        if($this->isSystemManager()) return true;

        //默认为配置文件中指定的后台模块
        if( ! $module) $module = '';
        //是否不需要验证的操作
        if($this->isNoNeedCheckPermission($module, $class, $function)) return true;

        //取回保存在session中的权限信息
        $permissionList = SC::getUserPermissionSession();

        //验证用户权限
        foreach($permissionList as $value)
        {
            if($value['module'] == $module && $value['class'] == $class && $value['action'] == $function)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是不是不需要验证权限
     * 
     * @param  string  $module   模块
     * @param  string  $class    类
     * @param  string  $function 函数
     * @return boolean           是｜否
     */
    public function isNoNeedCheckPermission($module, $class, $function)
    {
        //取回配置信息
        $info = Config::get('sys.access_public');

        //开始验证是不是需要验证权限
        foreach($info as $key => $value)
        {
            //判断module是否符合规则
            if( ! $this->checkModule($value, $module)) continue;

            //判断class是否符合规则
            $checkClass = $this->checkClass($value, $class);
            if($checkClass !== 'null')
            {
                if($checkClass === false) continue;
                if($checkClass === true) return true;
            }

            //判断function是否符合规则
            if($this->checkFunc($value, $function)) return true;
        }

        return false;
    }

    /**
     * 判断当前操作的功能是不是已经注册了
     * 
     * @param  string  $module   模块
     * @param  string  $class    类
     * @param  string  $function 函数
     * @return boolean           是｜否
     */
    public function checkIfHasReg($module, $class, $function)
    {
        if($module == 'foundation' and $class == 'acl') return true;
        //是否不需要验证的操作
        if($this->isNoNeedCheckPermission($module, $class, $function)) return true;
        //取回保存在session中的权限信息
        $permissionList = SC::getAllPermissionSession();
        if(empty($permissionList)) return false;
        foreach($permissionList as $value)
        {
            //验证用户权限
            if($value['module'] == $module && $value['class'] == $class && $value['action'] == $function)
                return true;
        }
        return false;
    }

    /**
     * call by self::isNoNeedCheckPermission
     */
    private function checkModule($value, $module)
    {
        if( ! isset($value['module']) or ! is_string($value['module']) or $value['module'] != $module)
        {
            return false;
        }
        return true;
    }

    /**
     * call by self::isNoNeedCheckPermission
     */
    private function checkClass($value, $class)
    {
        if( ! isset($value['class']) or ! is_string($value['class'])
            or ($value['class'] != $class and $value['class'] != '*')) return false;

        if($value['class'] == '*') return true;
        return 'null';
    }

    /**
     * call by self::isNoNeedCheckPermission
     */
    private function checkFunc($value, $function)
    {
        if( ! isset($value['function'])) return false;
        if(is_string($value['function']) and $value['function'] == '*') return true;
        if(is_string($value['function']) and $value['function'] == $function) return true;
        if(is_array($value['function']) and in_array($function, $value['function'])) return true;
        return false;
    }

    /**
     * 检测当前URI用户是否有权限进行
     * 
     * @see self::checkIfHasPermission
     * @return mixed
     */
    public function checkUriPermission($module, $class, $function)
    {
        return $this->checkIfHasPermission($module, $class, $function);
    }
    
    /**
     * 判断是不是超级用户
     *
     * @param object $userObj
     * @access public
     */
    public function isSystemManager($userObj = false)
    {
        if( ! $userObj) $userObj = SC::getLoginSession();
        if($userObj->group_id == self::ADMIN_ROLE_ID or $this->isSuperSystemManager($userObj)) return true;
        return false;
    }

    /**
     * 是否系统的创始人帐号
     * 
     * @return boolean true|false
     */
    public function isSuperSystemManager($userObj = false)
    {
        if( ! $userObj) $userObj = SC::getLoginSession();
        if($userObj->name == self::ADMIN_NAME or $userObj->id == self::ADMIN_ID) return true;
        return false;
    }

}
