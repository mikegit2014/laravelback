<?php namespace App\Services\Home\Content;

use Lang, Redis;
use App\Models\Home\Content as ContentModel;
use App\Libraries\Js;
use App\Services\BaseProcess;
use App\Services\Home\Consts\RedisKey;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

/**
 * 文章相关处理
 *
 * @author jiang <mylampblog@163.com>
 */
class Process extends BaseProcess
{
    /**
     * 阅读榜文章信息
     */
    public function articleTotalHot()
    {
        $articleIds = [];
        try { $articleIds = Redis::zrevrange(RedisKey::ARTICLE_TOTAL_VIEW, 0, 9); } catch (\Exception $e) {}

        if(empty($articleIds)) return [];
        $articleList = with(new ContentModel())->getContentInIds($articleIds);
        $result = [];
        foreach ($articleIds as $articleId) {
            foreach ($articleList as $akey => $articleInfo) {
                if ($articleInfo['id'] == $articleId) {
                    $result[] = $articleInfo;
                }
            }
        }
        return $result;
    }

    /**
     * 最近七天的阅读榜
     */
    public function articleLastSevenHot()
    {
        $days = $this->getSevenDays();
        $keys = array_map(function($date){
            return RedisKey::ARTICLE_EVERY_DAY_VIEW . $date;
        }, $days);
        $weight = [1, 1, 1, 1, 1, 1, 1];

        $articleIds = [];
        try {
            $sevenScore = Redis::zunionstore(RedisKey::ARTICLE_SEVEN_DAY_HOT, $keys, [ 'WEIGHTS' => $weight ]);
            $articleIds = Redis::zrevrange(RedisKey::ARTICLE_SEVEN_DAY_HOT, 0, 9);
        } catch (\Exception $e) {}

        if(empty($articleIds)) return [];
        $articleList = with(new ContentModel())->getContentInIds($articleIds);
        $result = [];
        foreach ($articleIds as $articleId) {
            foreach ($articleList as $akey => $articleInfo) {
                if ($articleInfo['id'] == $articleId) {
                    $result[] = $articleInfo;
                }
            }
        }
        return $result;
    }

    /**
     * 取得最近七天的日期
     */
    private function getSevenDays()
    {
        $today = date('Ymd');
        $days = [];
        for($i = 0; $i < 7; $i++ )
        {
            $days[] = $today - $i;
        }
        return $days;
    }

    /**
     * Rss
     */
    public function Rss()
    {
        $contentModel = new ContentModel();
        $articleList = $contentModel->getRss();
        return $this->prepareRss($articleList);
    }

    /**
     * prepare feed
     */
    private function prepareRss($articleList)
    {
        $feed = new Feed();
        $channel = new Channel();
        $time = strtotime(date('Y-m-d'));
        $copyrightYear = date('Y');

        $channel->title("Blog Rss")
            ->description("Blog Rss")
            ->url(config('sys.sys_blog_domain'))
            ->language('zh-CN')
            ->copyright('Copyright '.$copyrightYear.', Jiang')
            ->pubDate($time)
            ->lastBuildDate($time)
            ->ttl(3600 * 24)
            ->appendTo($feed);

        foreach($articleList as $key => $value)
        {
            $item = new Item();
            $item->title($value['title'])
            ->description($value['content'])
            ->url(route('blog.index.detail', ['id' => $value['id']]))
            ->pubDate($value['write_time'])
            ->guid(route('blog.index.detail', ['id' => $value['id']]), true)
            ->appendTo($channel);
        }
        return $feed->render();
    }

    /**
     * 文章总数
     */
    public function articleTotalNums()
    {
        $contentModel = new ContentModel();
        return $contentModel->articleNums();
    }

    /**
     * 每隔一段的时间更新一次文章浏览量到数据库
     *
     * @param int $currentViewNums 所要更新的阅读量
     */
    public function storeArticleViews($articleId, $currentViewNums)
    {
        if(empty($currentViewNums)) return false;
        with(new ContentModel())->updateViewNums($articleId, $currentViewNums);
    }

    /**
     * 文章的阅读数
     * 
     * @param  int $articleId 文章的id
     */
    public function articleViews($articleId)
    {
        return 10;
        $currentViewNums = Redis::get(RedisKey::ARTICLE_DETAIL_VIEW_ID.$articleId);
        return empty($currentViewNums) ? 0 : $currentViewNums;
    }

}