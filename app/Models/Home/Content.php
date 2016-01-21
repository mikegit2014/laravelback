<?php namespace App\Models\Home;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 文章表模型
 *
 * @author jiang
 */
class Content extends Model
{
    /**
     * 文章未删除的标识
     */
    CONST IS_DELETE_NO = 1;

    /**
     * 文章发布的标识
     */
    CONST STATUS_YES = 1;

    /**
     * 文章数据表名
     *
     * @var string
     */
    protected $table = 'article_main';

    /**
     * 关闭自动维护updated_at、created_at字段
     * 
     * @var boolean
     */
    public $timestamps = false;

    /**
     * 可以被集体附值的表的字段
     *
     * @var string
     */
    protected $fillable = array('id', 'count_view');

    /**
     * 表前缀
     * 
     * @var string
     */
    private $prefix;

    /**
     * 取得文章列表信息
     * 
     * @return array
     */
    public function activeArticleInfo($object)
    {
        $this->prefix = \DB:: getTablePrefix();
        $currentQuery = $this->select(\DB::raw($this->prefix.'article_main.*'))
                        ->leftJoin('article_classify_relation', 'article_classify_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_classify', 'article_classify_relation.classify_id', '=', 'article_classify.id')
                        ->leftJoin('article_tag_relation', 'article_tag_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_tags', 'article_tag_relation.tag_id', '=', 'article_tags.id')
                        ->where('article_main.is_delete', self::IS_DELETE_NO)->where('article_main.status', self::STATUS_YES)
                        ->groupBy('article_main.id')
                        ->orderBy('article_main.id', 'desc');
        if(isset($object->category) and is_numeric($object->category) and ! empty($object->category))
            $currentQuery->where('article_classify.id', $object->category);
        if(isset($object->tag) and is_numeric($object->tag) and ! empty($object->tag))
            $currentQuery->where('article_tags.id', $object->tag);
        $total = $currentQuery->get()->count();
        $currentQuery->forPage(
            $page = Paginator::resolveCurrentPage(),
            $perPage = 20
        );

        $data = $currentQuery->get()->all();

        return new LengthAwarePaginator($data, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath()
        ]);
    }

    /**
     * 取得一篇文章主表和副表的信息
     *
     * @param int $articleId 文章的ID
     * @return array
     */
    public function getContentDetailByArticleId($articleId)
    {
        $articleId = (int) $articleId;
        $this->prefix = \DB:: getTablePrefix();
        $currentQuery = $this->select(\DB::raw($this->prefix.'article_main.*, '.$this->prefix.'article_detail.content'))
                        ->leftJoin('article_detail', 'article_main.id', '=', 'article_detail.article_id')
                        ->leftJoin('article_classify_relation', 'article_classify_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_classify', 'article_classify_relation.classify_id', '=', 'article_classify.id')
                        ->leftJoin('article_tag_relation', 'article_tag_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_tags', 'article_tag_relation.tag_id', '=', 'article_tags.id')
                        ->where('article_main.is_delete', self::IS_DELETE_NO)->where('article_main.status', self::STATUS_YES)
                        ->where('article_main.id', $articleId)->first();
        $info = $currentQuery->toArray();
        return $info;
    }

    /**
     * 取得文章所属的标签
     * 
     * @param int $articleId 文章的ID
     * @return  array 文章的分类
     */
    public function getArticleClassify($articleId)
    {
        $articleId = (int) $articleId;
        $currentQuery = $this->from('article_classify_relation')->select(array('article_classify_relation.classify_id','article_classify.name'))
                ->leftJoin('article_classify', 'article_classify_relation.classify_id', '=', 'article_classify.id')
                ->where('article_classify_relation.article_id', $articleId)->get();
        $classify = $currentQuery->toArray();
        return $classify;
    }

    /**
     * 取得文章所属的标签
     * 
     * @param int 文章的ID
     * @return  array 文章的标签
     */
    public function getArticleTag($articleId)
    {
        $articleId = (int) $articleId;
        $currentQuery = $this->from('article_tag_relation')->select(array('article_tag_relation.tag_id', 'article_tags.name'))
              ->leftJoin('article_tags', 'article_tag_relation.tag_id', '=', 'article_tags.id')
              ->where('article_tag_relation.article_id', $articleId)->get();
        $tags = $currentQuery->toArray();
        return $tags;
    }

    /**
     * 根据文章的ID组取得文章的相关信息
     */
    public function getContentInIds($articleIds)
    {
        if( ! is_array($articleIds)) return [];
        $articleIds = array_map('intval', $articleIds);
        $list = $this->select(['id', 'title'])->whereIn('id', $articleIds)->get()->toArray();
        return $list;
    }

    /**
     * get RSS articles
     * 
     * @return array
     */
    public function getRss()
    {
        $info = $this->select(['article_main.id', 'article_main.title', 'article_main.summary', 'article_main.write_time', 'article_detail.content'])
                        ->leftJoin('article_detail', 'article_main.id', '=', 'article_detail.article_id')
                        ->leftJoin('article_classify_relation', 'article_classify_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_classify', 'article_classify_relation.classify_id', '=', 'article_classify.id')
                        ->leftJoin('article_tag_relation', 'article_tag_relation.article_id', '=', 'article_main.id')
                        ->leftJoin('article_tags', 'article_tag_relation.tag_id', '=', 'article_tags.id')
                        ->where('article_main.is_delete', self::IS_DELETE_NO)->where('article_main.status', self::STATUS_YES)
                        ->groupBy('article_main.id')
                        ->orderBy('article_main.id', 'desc')
                        ->limit(20)
                        ->get()
                        ->toArray();
        return $info;
    }

    /**
     * 取得文章的总数
     *
     * @todo 解决innodb count慢的问题
     * @return int 总数
     */
    public function articleNums()
    {
        $nums = $this->where('article_main.is_delete', self::IS_DELETE_NO)->where('article_main.status', self::STATUS_YES)->count();
        return $nums;
    }

    /**
     * 更新文章的阅读量
     *
     * @param int $articleId 文章的id
     * @param int $views 当前的文章阅读量
     */
    public function updateViewNums($articleId, $views)
    {
        $data = ['count_view' => $views];
        return $this->where('id', '=', intval($articleId))->update($data);
    }

}
