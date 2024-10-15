<?php

use wcf\system\event\EventHandler;

return static function (): void {
    $eventHandler = EventHandler::getInstance();

    $eventHandler->register(
        \wcf\event\worker\RebuildWorkerCollecting::class,
        static function (\wcf\event\worker\RebuildWorkerCollecting $event) {
            $event->register(wcf\system\worker\ConversationMessageRebuildDataWorker::class, -5);
            $event->register(wcf\system\worker\ConversationRebuildDataWorker::class, 0);
            $event->register(wcf\system\worker\ConversationMessageSearchIndexRebuildDataWorker::class, 300);
        }
    );
};
