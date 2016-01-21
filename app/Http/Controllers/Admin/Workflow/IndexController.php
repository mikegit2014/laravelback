<?php namespace App\Http\Controllers\Admin\Workflow;

use App\Http\Controllers\Admin\Controller;
use App\Services\Admin\Workflow\Process;
use App\Services\Admin\Workflow\Param\WorkflowSave;
use App\Libraries\Js;
use Request, Lang;

/**
 * 工作流
 *
 * @author jiang <mylampblog@163.com>
 */
class IndexController extends Controller
{
    /**
     * workflow process
     * 
     * @var object
     */
    private $workflowProcess;

    /**
     * workflow param
     * 
     * @var object
     */
    private $workflowSaveParam;

    /**
     * 初始化一些常用的类
     */
    public function __construct()
    {
        $this->workflowProcess = new Process();
        $this->workflowSaveParam = new WorkflowSave();
    }

    /**
     * 工作流管理
     */
    public function index()
    {
    	$list = $this->workflowProcess->workflowInfos();
    	$page = $list->setPath('')->appends(Request::all())->render();
        return view('admin.workflow.index', compact('list', 'page'));
    }

    /**
     * 增加新的工作流
     */
    public function add()
    {
        if(Request::method() == 'POST') return $this->saveDatasToDatabase();
        $formUrl = R('common', 'workflow.index.add');
        return view('admin.workflow.add', compact('formUrl'));
    }

    /**
     * 增加工作流入库处理
     *
     * @access private
     */
    private function saveDatasToDatabase()
    {
        $data = (array) Request::input('data');
        $data['addtime'] = time();

        $this->workflowSaveParam->setAttributes($data);
        if($this->workflowProcess->addWorkflow($this->workflowSaveParam) !== false) {
            $this->setActionLog();
            return Js::locate(R('common', 'workflow.index.index'), 'parent');
        }
        return Js::error($this->workflowProcess->getErrorMessage());
    }

    /**
     * 编辑工作流
     *
     * @access public
     */
    public function edit()
    {
        if(Request::method() == 'POST') {
            return $this->updateDatasToDatabase();
        }

        $id = Request::input('id');

        if( ! $id or ! is_numeric($id)) {
            return Js::error(Lang::get('common.illegal_operation'), true);
        }

        $info = $this->workflowProcess->workflowInfo(['id' => $id]);

        if(empty($info)) {
            return Js::error(Lang::get('workflow.workflow_not_found'));
        }

        $formUrl = R('common', 'workflow.index.edit');

        return view('admin.workflow.add',
            compact('info', 'formUrl', 'id')
        );
    }
    
    /**
     * 编辑工作流入库处理
     *
     * @access private
     */
    private function updateDatasToDatabase()
    {
        $data = Request::input('data');

        if( ! $data or ! is_array($data)) {
            return Js::error(Lang::get('common.illegal_operation'));
        }

        $this->workflowSaveParam->setAttributes($data);

        if($this->workflowProcess->editWorkflow($this->workflowSaveParam)) {
            $this->setActionLog();
            return Js::locate(R('common', 'workflow.index.index'), 'parent');
        }

        return Js::error($this->workflowProcess->getErrorMessage());
    }

    /**
     * 删除工作流
     *
     * @access public
     */
    public function delete()
    {
        $id = Request::input('id');
        if( ! $id ) return responseJson(Lang::get('common.action_error'));

        $id = array_map('intval', (array) $id);

        $info = $this->workflowProcess->workflowInfos(['ids' => $id]);

        if($this->workflowProcess->deleteWorkflow(['ids' => $id])) {
            $this->setActionLog(['workflowInfo' => $info]);
            return responseJson(Lang::get('common.action_success'), true);
        }
        
        return responseJson($this->workflowProcess->getErrorMessage());
    }


}