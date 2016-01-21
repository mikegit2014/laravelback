<?php namespace App\Services\Admin\ActionLog\Blog\Position;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\Position as PositionModel;
use App\Events\Admin\ActionLog;
use Request, Lang;

/**
 * 文章推荐位操作日志
 *
 * @author jiang <mylampblog@163.com>
 */
class Delete extends AbstractActionLog
{
    /**
     * 删除文章推荐位时的日志记录
     */
    public function handler()
    {
        if( ! $this->isLog()) return false;
        $extDatas = $this->getExtDatas();
        if( ! isset($extDatas['id'])) return false;
        $info = (new PositionModel())->getPositionInIds($extDatas['id']);
        foreach($info as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_position', ['name' => $value['name']])));
        }
    }
    
}
