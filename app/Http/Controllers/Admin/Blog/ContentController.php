<?php namespace App\Http\Controllers\Admin\Blog;

use Request, Lang, Session;
use App\Models\Admin\Content as ContentModel;
use App\Models\Admin\Category as CategoryModel;
use App\Models\Admin\User as UserModel;
use App\Models\Admin\Position as PositionModel;
use App\Models\Admin\Tags as TagsModel;
use App\Services\Admin\Content\Process as ContentProcess;
use App\Libraries\Js;
use App\Http\Controllers\Admin\Controller;
use App\Services\Admin\Content\Param\ContentSave;

/**
 * 登录相关
 *
 * @author jiang <mylampblog@163.com>
 */
class ContentController extends Controller
{
    /**
     * category model
     * 
     * @var object
     */
    private $categoryModel;

    /**
     * category model
     * 
     * @var object
     */
    private $contentModel;

    /**
     * user model
     * 
     * @var object
     */
    private $userModel;

    /**
     * position model
     * 
     * @var object
     */
    private $positionModel;

    /**
     * tag model
     * 
     * @var object
     */
    private $tagsModel;

    /**
     * content process
     * 
     * @var object
     */
    private $contentProcess;

    /**
     * content save
     * 
     * @var object
     */
    private $contentSave;

    /**
     * 初始化一些常用的类
     */
    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->contentModel = new ContentModel();
        $this->userModel = new UserModel();
        $this->positionModel = new PositionModel();
        $this->tagsModel = new TagsModel();
        $this->contentProcess = new ContentProcess();
        $this->contentSave = new ContentSave();
    }

    /**
     * 显示首页
     */
    public function index()
    {
        Session::flashInput(['http_referer' => Request::fullUrl()]);

        $search['keyword'] = strip_tags(Request::input('keyword'));
        $search['username'] = strip_tags(Request::input('username'));
        $search['classify'] = (int) Request::input('classify');
        $search['position'] = (int) Request::input('position');
        $search['tag'] = (int) Request::input('tag');
        $search['timeFrom'] = strip_tags(Request::input('time_from'));
        $search['timeTo'] = strip_tags(Request::input('time_to'));

        $list = $this->contentModel->AllContents($search);
        $page = $list->setPath('')->appends(Request::all())->render();
        $users = $this->userModel->userNameList();
        $classifyInfo = $this->categoryModel->activeCategory();
        $positionInfo = $this->positionModel->activePosition();
        $tagInfo = $this->tagsModel->activeTags();

        return view('admin.content.index',
            compact('list', 'page', 'users', 'classifyInfo', 'positionInfo', 'tagInfo', 'search')
        );
    }

    /**
     * 增加文章
     *
     * @access public
     */
    public function add()
    {
        if(Request::method() == 'POST') {
            return $this->saveDatasToDatabase();
        }

        $classifyInfo = $this->categoryModel->activeCategory();
        $formUrl = R('common', 'blog.content.add');

        return view('admin.content.add',
            compact('formUrl', 'classifyInfo')
        );
    }
    
    /**
     * 增加文章入库处理
     *
     * @access private
     */
    private function saveDatasToDatabase()
    {
        $data = (array) Request::input('data');
        $data['tags'] = explode(';', $data['tags']);

        $this->contentSave->setAttributes($data);

        if($this->contentProcess->addContent($this->contentSave) !== false) {
            $this->setActionLog(['param' => $this->contentSave]);
            return Js::locate(R('common', 'blog.content.index'), 'parent');
        }

        return Js::error($this->contentProcess->getErrorMessage());
    }

    /**
     * 删除文章
     *
     * @access public
     */
    public function delete()
    {
        if( ! $id = Request::input('id')) {
            return responseJson(Lang::get('common.action_error'));
        }

        $id = array_map('intval', (array) $id);

        if($this->contentProcess->detele($id))
        {
            $info = $this->contentModel->getArticleInIds($id);
            $this->setActionLog(['info' => $info]);
            return responseJson(Lang::get('common.action_success'), true);
        }

        return responseJson($this->contentProcess->getErrorMessage());
    }

    /**
     * 编辑文章
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

        if( ! $id or ! is_numeric($id)) {
            return Js::error(Lang::get('common.illegal_operation'));
        }

        $info = $this->contentModel->getContentDetailByArticleId($id);

        if(empty($info)) {
            return Js::error(Lang::get('content.not_found'));
        }

        $classifyInfo = $this->categoryModel->activeCategory();
        $info = $this->joinArticleClassify($info);
        $info = $this->joinArticleTags($info);

        $formUrl = R('common', 'blog.content.edit');

        return view('admin.content.add',
            compact('info', 'formUrl', 'id', 'classifyInfo')
        );
    }

    /**
     * 取回当前文章的所属分类
     * 
     * @param  array $articleInfo 当前文章的信息
     * @return array              整合后的当前文章信息
     */
    private function joinArticleClassify($articleInfo)
    {
        $classifyInfo = $this->contentModel->getArticleClassify($articleInfo['id']);
        $classifyIds = [];
        foreach ($classifyInfo as $key => $value)
        {
            $classifyIds[] = $value['classify_id'];
        }
        $articleInfo['classifyInfo'] = $classifyIds;
        return $articleInfo;
    }

    /**
     * 取回当前文章的所属标签
     * 
     * @param  array $articleInfo 当前文章的信息
     * @return array              整合后的当前文章信息
     */
    private function joinArticleTags($articleInfo)
    {
        $tagsInfo = $this->contentModel->getArticleTag($articleInfo['id']);
        $tagsIds = [];
        foreach ($tagsInfo as $key => $value)
        {
            $tagsIds[] = $value['name'];
        }
        $articleInfo['tagsInfo'] = $tagsIds;
        return $articleInfo;
    }
    
    /**
     * 编辑文章入库处理
     *
     * @access private
     */
    private function updateDatasToDatabase()
    {
        $httpReferer = Session::getOldInput('http_referer');

        $data = (array) Request::input('data');
        $data['tags'] = explode(';', $data['tags']);

        $this->contentSave->setAttributes($data);

        $id = intval(Request::input('id'));
        if($this->contentProcess->editContent($this->contentSave, $id) !== false) {
            $this->setActionLog(['param' => $this->contentSave]);
            $backUrl = ( ! empty($httpReferer)) ? $httpReferer : R('common', 'blog.content.index');
            return Js::locate($backUrl, 'parent');
        }

        return Js::error($this->contentProcess->getErrorMessage());
    }

    /**
     * 把文章关联到推荐位
     */
    public function position()
    {
        $ids = array_map('intval', (array) Request::input('ids'));
        $pids = array_map('intval', (array) Request::input('pids'));

        if($this->contentProcess->articlePositionRelation($ids, $pids) !== false) {
            $info = $this->contentModel->getArticleInIds($ids);
            $position = $this->positionModel->getPositionInIds($pids);
            $this->setActionLog(['info' => $info, 'position' => $position]);
            return responseJson(Lang::get('common.action_success'), true);
        }
        
        return responseJson(Lang::get('common.action_error'));
    }

}