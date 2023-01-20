<?php

namespace wcf\data\conversation\message;

/**
 * Represents a simplified version of ViewableConversationMessageList.
 * Disables the loading of attachments and embedded objects by default.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class SimplifiedViewableConversationMessageList extends ViewableConversationMessageList
{
    /**
     * @inheritDoc
     */
    protected $attachmentLoading = false;

    /**
     * @inheritDoc
     */
    protected $embeddedObjectLoading = false;
}
