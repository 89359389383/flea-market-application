<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Trade;

class TradeCompletedNotification extends Notification
{
    use Queueable;

    protected $trade;
    protected $evaluator;

    /**
     * Create a new notification instance.
     *
     * @param Trade $trade
     * @param \App\Models\User $evaluator  評価したユーザー
     */
    public function __construct(Trade $trade, $evaluator)
    {
        $this->trade = $trade;
        $this->evaluator = $evaluator;
    }

    /**
     * 通知の配信チャネル指定（メール）
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * メールメッセージの内容設定
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('取引完了のご連絡')
            ->greeting($notifiable->name . ' 様')
            ->line('評価者：' . $this->evaluator->name . ' さんがあなたの商品「' . $this->trade->item->name . '」の評価を送信しました。')
            ->line('評価者：' . $this->evaluator->name . ' さんを評価してください。')
            ->action('取引チャット画面へ', url(route('trade.chat.show', $this->trade->id)))
            ->line('今後ともCOACHTECHフリマをよろしくお願いいたします。');
    }

    /**
     * 他の通知チャネル用のメソッド（今回は不要なので省略可能）
     */
}
