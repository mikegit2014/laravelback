<?php namespace App\Services\Admin\ActionLog\Blog\Content;

use App\Services\Admin\AbstractActionLog;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Position extends AbstractActionLog
{
    /**
     * 推荐文章时的日志记录
     */
    public function handler()
    {
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['info'], $extDatas['position'])) return false;
        foreach($extDatas['info'] as $value)
        {
            foreach($extDatas['position'] as $position)
            {
                event(new ActionLog(Lang::get('actionlog.position_article', ['title' => $value['title'], 'position' => $position['name']])));
            }
        }
    }
    
}
