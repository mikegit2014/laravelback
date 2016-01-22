<?php

namespace App\Events\Home;

use App\Events\Event;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ArticleView extends Event
{
    public $articleId;

    /**
     * 文章阅读量的统计
     *
     * @return void
     */
    public function __construct($articleId)
    {
        $this->articleId = $articleId;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
