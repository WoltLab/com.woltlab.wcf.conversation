<?php

namespace wcf\system\importer;

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationBuilder;

/**
 * Imports conversations.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationImporter extends AbstractImporter
{
    /**
     * @inheritDoc
     */
    protected $className = Conversation::class;

    #[\Override]
    public function import(mixed $oldID, array $data, array $additionalData = [])
    {
        $oldUserID = $data['userID'];

        $builder = ConversationBuilder::forCreate()
            ->setSubject((string)($data['subject'] ?? ''))
            ->setTime((int)($data['time'] ?? 0))
            ->setUserID(ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']))
            ->setUsername((string)($data['username'] ?? ''));

        // check existing conversation
        if (\ctype_digit((string)$oldID)) {
            $existingConversation = new Conversation($oldID);
            if ($existingConversation->isNil()) {
                $builder->setID((int)$oldID);
            }
        }

        $handledColumns = ['conversationID', 'subject', 'time', 'userID', 'username'];
        foreach ($data as $key => $value) {
            if (\in_array($key, $handledColumns, true)) {
                continue;
            }
            if ($value !== null && !\is_string($value) && !\is_int($value) && !\is_float($value)) {
                continue;
            }

            $builder->setCustomProperty($key, $value);
        }

        $conversation = $builder->create();

        ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation', $oldID, $conversation->conversationID);

        // add author
        if (($data['isDraft'] ?? false) === false) {
            ImportHandler::getInstance()->getImporter('com.woltlab.wcf.conversation.user')->import(0, [
                'conversationID' => $oldID,
                'participantID' => $oldUserID,
                'username' => $data['username'],
                'hideConversation' => 0,
                'isInvisible' => 0,
                'lastVisitTime' => $data['time'],
            ], ['labelIDs' => []]);
        }

        return $conversation->conversationID;
    }
}
