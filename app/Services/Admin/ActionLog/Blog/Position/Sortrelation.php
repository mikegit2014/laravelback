<?php namespace App\Services\Admin\ActionLog\Blog\Position;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\PositionRelation as PositionRelationModel;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章推荐位排序管理操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Sortrelation extends AbstractActionLog
{
    /**
     * 编辑文章推荐位排序时的日志记录
     */
    public function handler()
    {
        if(Request::method() !== 'POST') return false;
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['prid'])) return false;
        $posArticle = (new PositionRelationModel())->getPositionArticleInIds(array($extDatas['prid']));
        foreach($posArticle as $value)
        {
            event(new ActionLog(Lang::get('actionlog.sort_position_relation', ['name' => $value['name']])));
        }
    }
    
}
