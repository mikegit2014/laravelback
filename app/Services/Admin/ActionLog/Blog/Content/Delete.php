<?php namespace App\Services\Admin\ActionLog\Blog\Content;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Delete extends AbstractActionLog
{
    /**
     * 删除文章时的日志记录
     */
    public function handler()
    {
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['info'])) return false;
        foreach($extDatas['info'] as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_article', ['title' => $value['title']])));
        }
    }
    
}
