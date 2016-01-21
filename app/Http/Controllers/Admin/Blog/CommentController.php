<?php namespace App\Http\Controllers\Admin\Blog;

use Request, Lang;
use App\Models\Admin\Comment as CommentModel;
use App\Services\Admin\Comment\Process;
use App\Libraries\Js;
use App\Http\Controllers\Admin\Controller;

/**
 * 文章评论相关
 *
 * @author jiang <mylampblog@163.com>
 */
class CommentController extends Controller
{
    /**
     * 分类的表操作对象
     * 
     * @var object
     */
    private $commentModel;

    /**
     * comment process
     * 
     * @var object
     */
    private $commentProcess;

    /**
     * 初始化一些常用的类
     */
    public function __construct()
    {
        $this->commentModel = new CommentModel();
        $this->commentProcess = new Process();
    }

    /**
     * 显示评论列表
     */
    public function index()
    {
        $list = $this->commentModel->allComment();
        $page = $list->setPath('')->appends(Request::all())->render();
        return view('admin.comment.index', compact('list', 'page'));
    }

    /**
     * 删除文章评论
     *
     * @access public
     */
    public function delete()
    {
        if( ! $id = Request::input('id')) {
            return responseJson(Lang::get('common.action_error'));
        }

        $id = array_map('intval', (array) $id);

        $comment = $this->commentModel->getCommentInIds($id);

        if($this->commentProcess->delete($id)) {
            $this->setActionLog(['comment' => $comment]);
            return responseJson(Lang::get('common.action_success'), true);
        }
        
        return responseJson($this->commentProcess->getErrorMessage());
    }

    /**
     * 评论
     *
     * @todo 和前台的使用通过的接口来，不用写两套代码。
     */
    public function reply()
    {
        if(Request::method() == 'POST') return $this->commentReply();
        $commentId = (int) Request::input('commentid');
        $view = $this->commentProcess->getReplyInfo($commentId);
        return response($view);
    }

    /**
     * 回复评论
     */
    private function commentReply()
    {
        $data['object_id'] = (int) Request::input('objectid');
        $data['object_type'] = (int) Request::input('object_type');
        $data['nickname'] = strip_tags(Request::input('nickname'));
        $data['content'] = strip_tags(Request::input('comment'));
        $data['replyid'] = (int) Request::input('replyid');

        $insertId = $this->commentProcess->addComment($data);
        
        if($insertId !== false) {
            $this->setActionLog(['replyid' => $data['replyid'], 'object_id' => $data['object_id'], 'content' => $data['content']]);
            return Js::execute('window.parent.loadComment('.$insertId.');');
        }

        return Js::error($this->commentProcess->getErrorMessage())
            .Js::execute('window.parent.reloadDialogTitle();');
    }

}