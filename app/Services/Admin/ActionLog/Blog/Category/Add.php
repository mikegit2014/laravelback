<?php namespace App\Services\Admin\ActionLog\Blog\Category;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章分类管理操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Add extends AbstractActionLog
{
    /**
     * 增加文章分类时的日志记录
     */
    public function handler()
    {
        if(Request::method() !== 'POST') return false;
        if( ! $this->isLog()) return false;
        $info = $this->getExtDatas();
        event(new ActionLog(Lang::get('actionlog.add_new_category', ['name' => $info['param']['name']])));
    }
    
}
