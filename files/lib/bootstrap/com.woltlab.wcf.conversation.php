<?php

use wcf\system\event\EventHandler;
use wcf\system\worker\event\RebuildWorkerCollecting;

return static function (): void {
    $eventHandler = EventHandler::getInstance();

    $eventHandler->register(RebuildWorkerCollecting::class, static function (RebuildWorkerCollecting $event) {
        $event->register(wcf\system\worker\ConversationMessageRebuildDataWorker::class, -5);
        $event->register(wcf\system\worker\ConversationRebuildDataWorker::class, 0);
        $event->register(wcf\system\worker\ConversationMessageSearchIndexRebuildDataWorker::class, 300);
    });
};
