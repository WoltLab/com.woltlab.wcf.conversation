<?php

use wcf\form\ConversationAddForm;
use wcf\system\event\EventHandler;
use wcf\system\request\LinkHandler;
use wcf\system\view\user\profile\UserProfileHeaderViewInteractionOption;
use wcf\system\WCF;

return static function (): void {
    $eventHandler = EventHandler::getInstance();

    $eventHandler->register(
        \wcf\event\worker\RebuildWorkerCollecting::class,
        static function (\wcf\event\worker\RebuildWorkerCollecting $event) {
            $event->register(\wcf\system\worker\ConversationMessageRebuildDataWorker::class, -5);
            $event->register(\wcf\system\worker\ConversationRebuildDataWorker::class, 0);
            $event->register(\wcf\system\worker\ConversationMessageSearchIndexRebuildDataWorker::class, 300);
        }
    );

    $eventHandler->register(
        \wcf\event\user\profile\UserProfileHeaderInteractionOptionCollecting::class,
        static function (\wcf\event\user\profile\UserProfileHeaderInteractionOptionCollecting $event) {
            if (
                MODULE_CONVERSATION
                && WCF::getUser()->userID
                && WCF::getSession()->getPermission('user.conversation.canUseConversation')
                && WCF::getSession()->getPermission('user.conversation.canStartConversation')
                && WCF::getUser()->userID != $event->user->userID
            ) {
                $event->register(UserProfileHeaderViewInteractionOption::forLink(
                    WCF::getLanguage()->get('wcf.conversation.button.add'),
                    LinkHandler::getInstance()->getControllerLink(ConversationAddForm::class, ['userID' => $event->user->userID])
                ));
            }
        }
    );

    $eventHandler->register(
        \wcf\event\endpoint\ControllerCollecting::class,
        static function (\wcf\event\endpoint\ControllerCollecting $event) {
            $event->register(new \wcf\system\endpoint\controller\core\conversations\GetConversationPopover());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\LeaveConversation());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\LeavePermanentlyConversation());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\RestoreConversation());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\GetConversationLeaveDialog());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\GetConversationLabels());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\AssignConversationLabels());
            $event->register(new \wcf\system\endpoint\controller\core\conversations\GetConversationLabelManager());
        }
    );
};
