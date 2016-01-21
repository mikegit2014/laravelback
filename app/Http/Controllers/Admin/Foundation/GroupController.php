<?php

namespace App\Http\Controllers\Admin\Foundation;

use App\Http\Controllers\Admin\Controller;
use Request, Lang, Session;
use App\Libraries\Js;
use App\Services\Admin\Acl\Acl;
use App\Services\Admin\Group\Validate\Group as GroupValidate;

/**
 * 用户组管理
 *
 * @author jiang <mylampblog@163.com>
 */
class GroupController extends Controller
{
    /**
     * 显示用户组列表
     *
     * @access public
     */
    public function index()
    {
        Session::flashInput(['http_referer' => Request::fullUrl()]);
        $grouplist = app('model.admin.group')->getAllGroupByPage();
        $page = $grouplist->setPath('')->appends(Request::all())->render();
        return view('admin.group.index', compact('grouplist', 'page'));
    }
    
    /**
     * 增加用户组
     *
     * @access public
     */
    public function add()
    {
        if(Request::method() == 'POST') {
            return $this->addDatasToDatabase();
        }
        $formUrl = R('common', 'foundation.group.add');
        return view('admin.group.add', compact('formUrl'));
    }
    
    /**
     * 增加用户组入库处理
     *
     * @access private
     */
    private function addDatasToDatabase()
    {
        app('param.admin.groupsave')->setAttributes(Request::input('data'));
        if(app('process.admin.group')->addGroup(app('param.admin.groupsave')) !== false) {
            $this->setActionLog();
            return Js::locate(R('common', 'foundation.group.index'), 'parent');
        }
        return Js::error(app('process.admin.group')->getErrorMessage());
    }

    /**
     * 删除用户组
     *
     * @access public
     */
    public function delete()
    {
        $id = with(new GroupValidate())->deleteIds( (array) Request::input('id'));
        if( ! $id or ! is_array($id)) {
            return responseJson(Lang::get('common.action_error'));
        }

        $groupInfos = app('model.admin.group')->getGroupInIds($id);

        if(app('process.admin.group')->detele($id)) {
            $this->setActionLog(['groupInfos' => $groupInfos]);
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson(
            app('process.admin.group')->getErrorMessage()
        );
    }
    
    /**
     * 编辑用户组
     *
     * @access public
     */
    public function edit()
    {
        if(Request::method() == 'POST') {
            return $this->updateDatasToDatabase();
        }

        Session::flashInput(['http_referer' => Session::getOldInput('http_referer')]);

        $id = Request::input('id');
        $groupId = url_param_decode($id);

        if( ! $groupId or ! is_numeric($groupId)) {
            return Js::error(Lang::get('common.illegal_operation'));
        }

        $groupInfo = app('model.admin.group')->getOneGroupById($groupId);
        if(empty($groupInfo)) {
            return Js::error(Lang::get('group.group_not_found'));
        }

        if( ! app('admin.acl')->checkGroupLevelPermission($groupId, Acl::GROUP_LEVEL_TYPE_GROUP)) {
            return Js::error(Lang::get('common.account_level_deny'), true);
        }
        
        $formUrl = R('common', 'foundation.group.edit');

        return view('admin.group.add',
            compact('groupInfo', 'formUrl', 'id')
        );
    }
    
    /**
     * 编辑用户组入库处理
     *
     * @access private
     */
    private function updateDatasToDatabase()
    {
        $httpReferer = Session::getOldInput('http_referer');
        $data = (array) Request::input('data');
        
        app('param.admin.groupsave')->setAttributes($data);

        if(app('process.admin.group')->editGroup(app('param.admin.groupsave'))) {
            $this->setActionLog();
            $backUrl = ( ! empty($httpReferer)) ? $httpReferer : R('common', 'foundation.group.index');
            return Js::locate($backUrl, 'parent');
        }
        
        return Js::error(app('process.admin.group')->getErrorMessage());
    }

}