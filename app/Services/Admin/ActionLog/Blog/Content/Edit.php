<?php namespace App\Services\Admin\ActionLog\Blog\Content;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章管理操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Edit extends AbstractActionLog
{
    /**
     * 编辑文章时的日志记录
     */
    public function handler()
    {
        if(Request::method() !== 'POST') return false;
        if( ! $this->isLog()) return false;
        $info = $this->getExtDatas();
        event(new ActionLog(Lang::get('actionlog.edit_article', ['title' => $info['param']['title']])));
    }
    
}
