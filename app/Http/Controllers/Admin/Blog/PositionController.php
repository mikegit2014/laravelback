<?php namespace App\Http\Controllers\Admin\Blog;

use Request, Lang, Session;
use App\Models\Admin\Position as PositionModel;
use App\Models\Admin\PositionRelation as PositionRelationModel;
use App\Models\Admin\Content as ContentModel;
use App\Services\Admin\Position\Process as PositionProcess;
use App\Libraries\Js;
use App\Http\Controllers\Admin\Controller;
use App\Services\Admin\Position\Param\PositionSave;

/**
 * 文章推荐位相关
 *
 * @author jiang <mylampblog@163.com>
 */
class PositionController extends Controller
{
    /**
     * position model
     * 
     * @var object
     */
    private $positionModel;

    /**
     * pr model
     * 
     * @var object
     */
    private $prModel;

    /**
     * content model
     * 
     * @var object
     */
    private $contentModel;

    /**
     * p process
     * 
     * @var object
     */
    private $pProcess;

    /**
     * p save
     * 
     * @var object
     */
    private $pSave;

    /**
     * 初始化一些常用的类
     */
    public function __construct()
    {
        $this->positionModel = new PositionModel();
        $this->prModel = new PositionRelationModel();
        $this->contentModel = new ContentModel();
        $this->pProcess = new PositionProcess();
        $this->pSave = new PositionSave();
    }

    /**
     * 显示推荐位列表
     */
    public function index()
    {
        Session::flashInput(['http_referer' => Request::fullUrl()]);
        $list = $this->positionModel->unDeletePosition();
        $page = $list->setPath('')->appends(Request::all())->render();
        return view('admin.content.position', compact('list', 'page'));
    }

    /**
     * 增加推荐位分类
     */
    public function add()
    {
        if(Request::method() == 'POST') return $this->saveDatasToDatabase();
        $formUrl = R('common', 'blog.position.add');
        return view('admin.content.positionadd', compact('formUrl'));
    }

    /**
     * 增加推荐位入库处理
     *
     * @access private
     */
    private function saveDatasToDatabase()
    {
        $this->pSave->setAttributes((array) Request::input('data'));
        if($this->pProcess->addPosition($this->pSave) !== false) {
            $this->setActionLog(['param' => $this->pSave]);
            return Js::locate(R('common', 'blog.position.index'), 'parent');
        }
        return Js::error($this->pProcess->getErrorMessage());
    }

    /**
     * 编辑文章推荐位
     */
    public function edit()
    {
        if(Request::method() == 'POST') {
            return $this->updateDatasToDatabase();
        }

        Session::flashInput(['http_referer' => Session::getOldInput('http_referer')]);

        $id = Request::input('id');

        if( ! $id or ! is_numeric($id)) {
            return Js::error(Lang::get('common.illegal_operation'));
        }

        $info = $this->positionModel->getOneById($id);

        if(empty($info)) {
            return Js::error(Lang::get('position.not_found'));
        }

        $formUrl = R('common', 'blog.position.edit');
        return view('admin.content.positionadd',
            compact('info', 'formUrl', 'id')
        );
    }

    /**
     * 编辑推荐位入库处理
     *
     * @access private
     */
    private function updateDatasToDatabase()
    {
        $httpReferer = Session::getOldInput('http_referer');

        $data = Request::input('data');

        if( ! $data or ! is_array($data)) {
            return Js::error(Lang::get('common.illegal_operation'));
        }

        $this->pSave->setAttributes($data);

        if($this->pProcess->editPosition($this->pSave)) {
            $this->setActionLog(['param' => $this->pSave]);
            $backUrl = ( ! empty($httpReferer)) ? $httpReferer : R('common', 'blog.position.index');
            return Js::locate($backUrl, 'parent');
        }

        return Js::error($this->pProcess->getErrorMessage());
    }

    /**
     * 删除文章推荐位
     *
     * @access public
     */
    public function delete()
    {
        if( ! $id = Request::input('id')) {
            return responseJson(Lang::get('common.action_error'));
        }

        $id = array_map('intval', (array) $id);

        if($this->pProcess->detele($id)) {
            $this->setActionLog(['id' => $id]);
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson($this->pProcess->getErrorMessage());
    }

    /**
     * 查看文章关联
     */
    public function relation()
    {
        $positionId = (int) Request::input('position');
        $list = $this->contentModel->positionArticle($positionId);
        $page = $list->setPath('')->appends(Request::all())->render();
        $positionInfo = $this->positionModel->activePosition();
        return view('admin.content.positionarticle',
            compact('list', 'page', 'positionInfo', 'positionId')
        );
    }

    /**
     * 删除推荐位关联文章
     */
    public function delrelation()
    {
        if( ! $prid = Request::input('prid')) {
            return responseJson(Lang::get('common.action_error'));
        }

        if( ! is_array($prid)) $prid = array($prid);

        $posArticle = $this->prModel->getPositionArticleInIds($prid);

        if($this->pProcess->delRelation($prid)) {
            $this->setActionLog(['posArticle' => $posArticle]);
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson($this->pProcess->getErrorMessage());
    }

    /**
     * 排序关联的文章
     */
    public function sortrelation()
    {
        $data = (array) Request::input('data');
        $prid = '';

        foreach($data as $key => $value) {
            $prid = $value['prid'];
            $update = $this->pProcess->sortRelation($value['prid'], $value['sort']);
            if($update === false) $err = true;
        }

        if(isset($err)) {
            return responseJson(Lang::get('common.action_error'));
        }

        $this->setActionLog(['prid' => $prid]);

        return responseJson(
            Lang::get('common.action_success'),
            true
        );
    }

}