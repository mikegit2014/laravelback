<?php namespace App\Services\Admin\ActionLog\Blog\Comment;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章评论操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Delete extends AbstractActionLog
{
    /**
     * 删除文章评论时的日志记录
     */
    public function handler()
    {
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['comment'])) return false;
        foreach($extDatas['comment'] as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_comment', ['content' => $value['content']])));
        }
    }
    
}
