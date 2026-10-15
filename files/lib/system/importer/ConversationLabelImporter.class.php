<?php

namespace wcf\system\importer;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelBuilder;

/**
 * Imports conversation labels.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class ConversationLabelImporter extends AbstractImporter
{
    /**
     * @inheritDoc
     */
    protected $className = ConversationLabel::class;

    #[\Override]
    public function import(mixed $oldID, array $data, array $additionalData = [])
    {
        $userID = ImportHandler::getInstance()->getNewID('com.woltlab.wcf.user', $data['userID']);
        if ($userID === null) {
            return 0;
        }

        $builder = ConversationLabelBuilder::forCreate()
            ->setUserID($userID)
            ->setLabel((string)($data['label'] ?? ''))
            ->setCssClassName((string)($data['cssClassName'] ?? ''));

        $handledColumns = ['labelID', 'userID', 'label', 'cssClassName'];
        foreach ($data as $key => $value) {
            if (\in_array($key, $handledColumns, true)) {
                continue;
            }
            if ($value !== null && !\is_string($value) && !\is_int($value) && !\is_float($value)) {
                continue;
            }

            $builder->setCustomProperty($key, $value);
        }

        $label = $builder->create();

        ImportHandler::getInstance()->saveNewID('com.woltlab.wcf.conversation.label', $oldID, $label->labelID);

        return $label->labelID;
    }
}
