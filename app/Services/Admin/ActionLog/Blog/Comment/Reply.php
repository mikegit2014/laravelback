<?php namespace App\Services\Admin\ActionLog\Blog\Comment;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\Comment as CommentModel;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章评论操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Reply extends AbstractActionLog
{
    /**
     * 回复文章评论时的日志记录
     */
    public function handler()
    {
        if(Request::method() !== 'POST') return false;
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['replyid'], $extDatas['object_id'], $extDatas['content'])) return false;
        $info = with(new CommentModel())->getCommentArticleByIdArticleId($extDatas['replyid'], $extDatas['object_id']);
        event(new ActionLog(Lang::get('actionlog.reply_comment', ['title' => $info['title'], 'content' => $info['content'], 'replycontent' => $extDatas['content']])));
    }
    
}
