<?php

namespace wcf\data\conversation\label;

use wcf\data\DatabaseObject;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a conversation label.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @property-read   int     $labelID        unique id of the conversation label
 * @property-read   int     $userID         id of the user who created the conversation label
 * @property-read   string  $label          name of the conversation label
 * @property-read   string  $cssClassName   CSS class name of the conversation label handeling its appearance (color)
 */
class ConversationLabel extends DatabaseObject
{
    /**
     * list of pre-defined css class names
     * @var string[]
     * @deprecated 6.2 No longer in use.
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
     * @var array<int, ConversationLabel>
     */
    private static array $userLabels;

    /**
     * Returns the conversation labels of the active user.
     *
     * @return array<int, ConversationLabel>
     * @since 6.2
     */
    public static function getUserLabels(): array
    {
        if (!isset(self::$userLabels)) {
            $labelList = new ConversationLabelList();
            $labelList->getConditionBuilder()->add("conversation_label.userID = ?", [WCF::getUser()->userID]);
            $labelList->readObjects();

            self::$userLabels = $labelList->getObjects();
        }

        return self::$userLabels;
    }

    /**
     * Returns a list of conversation labels for given user id.
     *
     * @return ConversationLabelList
     * @deprecated 6.2 Use `ConversationLabel::getUserLabels()` instead.
     */
    public static function getLabelsByUser(?int $userID = null)
    {
        $userID ??= WCF::getUser()->userID;

        $labelList = new ConversationLabelList();
        $labelList->getConditionBuilder()->add("conversation_label.userID = ?", [$userID]);
        $labelList->readObjects();

        return $labelList;
    }

    /**
     * Returns a list of available CSS class names.
     *
     * @return string[]
     * @deprecated 6.2 No longer in use.
     */
    public static function getLabelCssClassNames()
    {
        return self::$availableCssClassNames;
    }

    public function render(): string
    {
        $cssClassName = StringUtil::encodeHTML($this->cssClassName !== '' ? ' ' . $this->cssClassName : '');
        $title = StringUtil::encodeHTML($this->label);

        return <<<HTML
            <span class="badge label{$cssClassName}">{$title}</span>
        HTML;
    }
}
