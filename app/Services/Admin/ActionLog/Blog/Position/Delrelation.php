<?php namespace App\Services\Admin\ActionLog\Blog\Position;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\PositionRelation as PositionRelationModel;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章推荐位取消文章关联操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Delrelation extends AbstractActionLog
{
    /**
     * 文章推荐位取消文章关联时的日志记录
     */
    public function handler()
    {
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['posArticle'])) return false;
        foreach($extDatas['posArticle'] as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_position_relation', ['name' => $value['name'], 'title' => $value['title']])));
        }
    }
    
}
