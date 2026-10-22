<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $projectId;
    public $message;

    public function __construct($projectId, $message)
    {
        $this->projectId = $projectId;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // Canal restrito a este projeto
        return new PrivateChannel('project.' . $this->projectId); 
    }
}