<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ScheduleTaskFailed extends Notification
{
    use Queueable;

    private $taskName;
    private $error;

    public function __construct($taskName, $error = null)
    {
        $this->taskName = $taskName;
        $this->error = $error;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('スケジュールタスクエラー: ' . $this->taskName)
            ->greeting('スケジュールタスクの実行に失敗しました')
            ->line('タスク名: ' . $this->taskName)
            ->line('エラー内容: ' . ($this->error ?? '不明なエラー'))
            ->line('発生時刻: ' . now()->format('Y-m-d H:i:s'))
            ->action('管理画面を確認', url('/admin/dashboard'));
    }
}
