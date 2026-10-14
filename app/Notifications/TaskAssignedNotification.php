<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Models\Task;

class TaskAssignedNotification extends Notification
{
    public Task $task;
    public ?string $creatorName;

    public function __construct(Task $task, ?string $creatorName = null)
    {
        $this->task = $task;
        $this->creatorName = $creatorName;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $byText = $this->creatorName ? " oleh {$this->creatorName}" : '';
        $dueText = $this->task->due_date ? " (Tenggat: " . date('d M Y', strtotime($this->task->due_date)) . ")" : '';

        return [
            'id' => (string) $this->task->id,
            'type' => 'task',
            'title' => 'Tugas Baru Ditugaskan',
            'message' => "Anda diberikan tugas baru: '{$this->task->title}'{$byText}{$dueText}.",
            'link' => '/tasks',
            'task_id' => $this->task->id,
            'priority' => $this->task->priority,
        ];
    }
}
