<?php namespace App\Services\Admin\ActionLog\Blog\Category;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\Category as CategoryModel;
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
        if( ! isset($extDatas['id'])) return false;
        $info = (new CategoryModel())->getArticleCategorysInIds($extDatas['id']);
        foreach($info as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_category', ['name' => $value['name']])));
        }
    }
    
}
