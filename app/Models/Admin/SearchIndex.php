<?php namespace App\Models\Admin;

use App\Models\Admin\Base;
use App\Models\Admin\Content as ContentModel;

/**
 * 文章表模型
 *
 * @author jiang
 */
class SearchIndex extends Base
{
    /**
     * 文章数据表名
     *
     * @var string
     */
    protected $table = 'search_index';

    /**
     * 可以被集体附值的表的字段
     *
     * @var string
     */
    protected $fillable = array('id', 'article_id', 'title', 'summary', 'content', 'added_date', 'edited_date');

    /**
     * 保存字典索引
     *
     * @param array $data 要保存的数据
     * @return boolean
     */
    public function saveIndex(array $check, array $data)
    {
        return $this->updateOrCreate($check, $data);
    }

    /**
     * 批量删除
     *
     * @param array $articleIds 文章的id
     */
    public function deleteArticleDictIndex(array $articleIds)
    {
        return $this->whereIn('article_id', $articleIds)->delete();
    }

    /**
     * 用于自动删除脏数据
     */
    public function clearDirtySearchIndexData()
    {
        $prefix = \DB:: getTablePrefix();
        $whereRaw = "article_id in (select id from `{$prefix}article_main` where is_delete=".ContentModel::IS_DELETE_YES.")";
        return $this->whereRaw($whereRaw)->delete();
    }
    

}
