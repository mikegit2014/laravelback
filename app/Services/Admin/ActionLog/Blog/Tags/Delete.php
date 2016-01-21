<?php namespace App\Services\Admin\ActionLog\Blog\Tags;

use App\Services\Admin\AbstractActionLog;
use App\Models\Admin\Tags as TagsModel;
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
        $info = (new TagsModel())->getTagsInIds($extDatas['id']);
        foreach($info as $value)
        {
            event(new ActionLog(Lang::get('actionlog.delete_tags', ['name' => $value['name']])));
        }
    }
    
}
