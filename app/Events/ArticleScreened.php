<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleScreened implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $projectId;
    public $articleId;
    public $userName;

    public function __construct($projectId, $articleId, $userName)
    {
        $this->projectId = $projectId;
        $this->articleId = $articleId;
        $this->userName = $userName;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('project.' . $this->projectId);
    }
}
