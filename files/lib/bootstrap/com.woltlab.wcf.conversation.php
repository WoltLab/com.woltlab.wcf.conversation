<?php

use wcf\data\DatabaseObject;
use wcf\data\user\UserProfile;
use wcf\system\event\EventHandler;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

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
        \wcf\event\user\UsersMerging::class,
        static function (\wcf\event\user\UsersMerging $event) {
            $event->reassignUserColumn('wcf1_conversation', usernameColumn: 'username');
            $event->reassignUserColumn('wcf1_conversation_message', usernameColumn: 'username');
            $event->reassignUserColumn('wcf1_conversation_label');
            $event->reassignUserColumn('wcf1_conversation_to_user', 'participantID', 'username', ignore: true);
        }
    );

    $eventHandler->register(
        \wcf\event\user\UserRenamed::class,
        static function (\wcf\event\user\UserRenamed $event) {
            $event->updateUsernameColumn('wcf1_conversation');
            $event->updateUsernameColumn('wcf1_conversation_message');
            $event->updateUsernameColumn('wcf1_conversation', 'lastPosterID', 'lastPoster');
            $event->updateUsernameColumn('wcf1_conversation_to_user', 'participantID');
        }
    );

    if (
        \MODULE_CONVERSATION===1
        && !WCF::getUser()->isGuest()
        && WCF::getSession()->hasPermission('user.conversation.canUseConversation')
        && WCF::getSession()->hasPermission('user.conversation.canStartConversation')
    ) {
        $eventHandler->register(
            \wcf\event\interaction\user\UserProfileInteractionCollecting::class,
            static function (\wcf\event\interaction\user\UserProfileInteractionCollecting $event) {
                $event->provider->addInteraction(
                    new class(
                        'start-conversation',
                        isAvailableCallback: static fn(UserProfile $user) => WCF::getUser()->userID !== $user->userID
                    ) extends \wcf\system\interaction\AbstractInteraction {
                        #[\Override]
                        public function render(DatabaseObject $object): string
                        {
                            \assert($object instanceof UserProfile);

                            return \sprintf(
                                '<a href="%s">%s</a>',
                                StringUtil::encodeHTML(
                                    LinkHandler::getInstance()->getControllerLink(
                                        \wcf\form\ConversationAddForm::class,
                                        ['userID' => $object->userID]
                                    )
                                ),
                                WCF::getLanguage()->get('wcf.conversation.button.add')
                            );
                        }
                    }
                );
            }
        );
    }
};
