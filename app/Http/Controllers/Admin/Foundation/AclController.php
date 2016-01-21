<?php

namespace App\Http\Controllers\Admin\Foundation;

use App\Http\Controllers\Admin\Controller;
use Request, Lang, Session;
use App\Libraries\Js;
use App\Services\Admin\Acl\Acl;
use App\Services\Admin\Tree;
use App\Services\Admin\SC;

/**
 * 权限菜单管理相关
 *
 * @author jiang <mylampblog@163.com>
 */
class AclController extends Controller
{
    /**
     * 显示权限列表
     *
     * @access public
     */
    public function index()
    {
        Session::flashInput(['http_referer' => Request::fullUrl()]);
        $pid = (int) Request::input('pid', 'all');
        $list = Tree::genTree(app('model.admin.permission')->getAllAccessPermission());
        return view('admin.acl.index', compact('list', 'pid'));
    }

    /**
     * 增加权限功能
     *
     * @access public
     */
    public function add()
    {
        if (Request::method() == 'POST') {
            return $this->addPermissionToDatabase();
        }

        $select = Tree::dropDownSelect(
            Tree::genTree(app('model.admin.permission')->getAllAccessPermission())
        );

        $formUrl = R('common', 'foundation.acl.add');

        return view('admin.acl.add',
            compact('select', 'formUrl')
        );
    }

    /**
     * 增加功能权限入库
     *
     * @access private
     */
    private function addPermissionToDatabase()
    {
        $data = (array) Request::input('data');
        $data['add_time'] = time();

        app('param.admin.aclsave')->setAttributes($data);

        if(app('process.admin.acl')->addAcl(app('param.admin.aclsave')) !== false) {
            return Js::locate(R('common', 'foundation.acl.index'), 'parent');
        }

        return Js::error(app('process.admin.acl')->getErrorMessage());
    }
    
    /**
     * 删除权限功能
     *
     * @access public
     */
    public function delete()
    {
        $permissionId = (array) Request::input('id');

        if(app('process.admin.acl')->detele($permissionId) !== false) {
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson(app('process.admin.acl')->getErrorMessage());
    }
    
    /**
     * 编辑权限功能
     *
     * @access public
     */
    public function edit()
    {
        if(Request::method() == 'POST') {
            return $this->updatePermissionToDatabase();
        }

        Session::flashInput(['http_referer' => Session::getOldInput('http_referer')]);

        $id = Request::input('id');
        $permissionId = url_param_decode($id);

        if( ! $permissionId or ! is_numeric($permissionId)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        $list = Tree::genTree(app('model.admin.permission')->getAllAccessPermission());
        $permissionInfo = app('model.admin.permission')->getOnePermissionById($permissionId);

        if(empty($permissionInfo)) {
            return Js::error(Lang::get('common.acl_not_found'), true);
        }

        $select = Tree::dropDownSelect($list, $permissionInfo['pid']);
        $formUrl = R('common', 'foundation.acl.edit');

        return view('admin.acl.add',
            compact('select', 'permissionInfo', 'formUrl', 'id')
        );
    }
    
    /**
     * 编辑功能权限入库
     *
     * @access private
     */
    private function updatePermissionToDatabase()
    {
        $httpReferer = Session::getOldInput('http_referer');

        app('param.admin.aclsave')->setAttributes(Request::input('data'));

        if(app('process.admin.acl')->editAcl(app('param.admin.aclsave')) !== false) {
            $backUrl = ( ! empty($httpReferer)) ? $httpReferer : R('common', 'foundation.acl.index'); 
            return Js::locate($backUrl, 'parent');
        }

        return Js::error(app('process.admin.acl')->getErrorMessage());
    }
    
    /**
     * 排序权限功能
     *
     * @access public
     */
    public function sort()
    {
        $sort = Request::input('sort');

        if( ! $sort or ! is_array($sort)) {
            return Js::error(Lang::get('common.choose_checked'));
        }

        foreach($sort as $key => $value) {
            if(app('model.admin.permission')->sortPermission($key, $value) === false) {
                $err = true;
            }
        }

        if(isset($err)) {
            return Js::error(Lang::get('common.action_error'));
        }

        return Js::locate(
            R('common', 'foundation.acl.index'),
            'parent'
        );
    }

    /**
     * 对用户进行权限设置
     * 
     * @access public
     */
    public function user()
    {
        if(Request::method() == 'POST') {
            return $this->saveUserPermissionToDatabase();
        }

        $id = url_param_decode(Request::input('id'));
        if( ! $id or ! is_numeric($id)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        $info = app('model.admin.user')->getOneUserById(intval($id));
        if(empty($info)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        if( ! app('admin.acl')->checkGroupLevelPermission($id, Acl::GROUP_LEVEL_TYPE_USER)) {
            return Js::error(Lang::get('common.account_level_deny'), true);
        }

        $zTree = app('process.admin.acl')->prepareDataForZtree(app('process.admin.acl')->getUserAccessPermissionIds($id));
        $all = app('process.admin.acl')->prepareUserPermissionIds();

        $router = 'user';

        return view('admin.acl.setpermission',
            compact('zTree', 'id', 'info', 'router', 'all')
        );
    }

    /**
     * 用户权限入库
     * 
     * @return boolean true|false
     */
    private function saveUserPermissionToDatabase()
    {
        $this->checkFormHash();

        $id = Request::input('id');
        $all = Request::input('all');

        if( ! $id or ! is_numeric($id) or ! $all) {
            return responseJson(Lang::get('common.illegal_operation'));
        }

        app('param.admin.aclset')->setPermission(Request::input('permission'))->setAll($all)->setId($id);

        if( ! app('process.admin.acl')->setUserAcl(app('param.admin.aclset'))) {
            return responseJson(app('process.admin.acl')->getErrorMessage());
        }

        $this->setActionLog();

        return responseJson(
            Lang::get('common.action_success')
        );
        
    }
    
    /**
     * 对用户组进行权限设置
     * 
     * @access public
     */
    public function group()
    {
        if(Request::method() == 'POST') {
            return $this->saveGroupPermissionToDatabase();
        }

        $id = url_param_decode(Request::input('id'));
        if( ! $id or ! is_numeric($id)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        $info = app('model.admin.group')->getOneGroupById(intval($id));
        if(empty($info)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        if( ! app('admin.acl')->checkGroupLevelPermission($id, Acl::GROUP_LEVEL_TYPE_GROUP)) {
            return Js::error(Lang::get('common.account_level_deny'), true);
        }

        $zTree = app('process.admin.acl')->prepareDataForZtree(app('process.admin.acl')->getGroupAccessPermissionIds($id));
        $all = app('process.admin.acl')->prepareUserPermissionIds();

        $router = 'group';
        return view('admin.acl.setpermission',
            compact('zTree', 'id', 'info', 'router', 'all')
        );
    }

    /**
     * 用户组权限入库
     * 
     * @return boolean true|false
     */
    private function saveGroupPermissionToDatabase()
    {
        $this->checkFormHash();

        $id = Request::input('id');
        $all = Request::input('all');

        if( ! $id or ! is_numeric($id) or ! $all) {
            return responseJson(Lang::get('common.illegal_operation'));
        }

        if( ! app('admin.acl')->checkGroupLevelPermission($id, Acl::GROUP_LEVEL_TYPE_GROUP)) {
            return responseJson(Lang::get('common.account_level_deny'));
        }

        app('param.admin.aclset')->setPermission(Request::input('permission'))->setAll($all)->setId($id);
        
        if( ! app('process.admin.acl')->setGroupAcl(app('param.admin.aclset'))) {
            return responseJson(app('process.admin.acl')->getErrorMessage());
        }

        $this->setActionLog();

        return responseJson(
            Lang::get('common.action_success')
        );
    }

}