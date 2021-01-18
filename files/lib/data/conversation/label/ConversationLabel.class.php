<?php

namespace wcf\data\conversation\label;

use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a conversation label.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation\Label
 *
 * @property-read   integer $labelID    unique id of the conversation label
 * @property-read   integer $userID     id of the user who created the conversation label
 * @property-read   string $label      name of the conversation label
 * @property-read   string $cssClassName   CSS class name of the conversation label handeling its appearance (color)
 */
class ConversationLabel extends DatabaseObject
{
    /**
     * list of pre-defined css class names
     * @var string[]
     */
    public static $availableCssClassNames = [
        'yellow',
        'orange',
        'brown',
        'red',
        'pink',
        'purple',
        'blue',
        'green',
        'black',

        'none', /* not a real value */
    ];

    /**
     * Returns a list of conversation labels for given user id.
     *
     * @param integer $userID
     * @return  ConversationLabelList
     */
    public static function getLabelsByUser($userID = null)
    {
        if ($userID === null) {
            $userID = WCF::getUser()->userID;
        }

        $labelList = new ConversationLabelList();
        $labelList->getConditionBuilder()->add("conversation_label.userID = ?", [$userID]);
        $labelList->readObjects();

        return $labelList;
    }

    /**
     * Returns a list of available CSS class names.
     *
     * @return  string[]
     */
    public static function getLabelCssClassNames()
    {
        return self::$availableCssClassNames;
    }
}
