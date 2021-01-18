<?php

namespace wcf\data\conversation;

use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\data\DatabaseObjectDecorator;
use wcf\data\TLegacyUserPropertyAccess;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Represents a viewable conversation.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\Data\Conversation
 *
 * @method  Conversation    getDecoratedObject()
 * @mixin   Conversation
 * @property-read   integer|null $otherParticipantID
 * @property-read   string|null $otherParticipant
 */
class ViewableConversation extends DatabaseObjectDecorator
{
    use TLegacyUserPropertyAccess;

    /**
     * participant summary
     * @var string
     */
    protected $__participantSummary;

    /**
     * user profile object
     * @var UserProfile
     */
    protected $userProfile;

    /**
     * last poster's profile
     * @var UserProfile
     */
    protected $lastPosterProfile;

    /**
     * other participant's profile
     * @var UserProfile
     */
    protected $otherParticipantProfile;

    /**
     * list of assigned labels
     * @var ConversationLabel[]
     */
    protected $labels = [];

    /**
     * @inheritDoc
     */
    protected static $baseClass = Conversation::class;

    /**
     * maps legacy direct access to last poster's user profile data to the real
     * user profile property names
     * @var string[]
     * @deprecated
     */
    protected static $__lastUserAvatarPropertyMapping = [
        'lastPosterAvatarID' => 'avatarID',
        'lastPosterAvatarName' => 'avatarName',
        'lastPosterAvatarExtension' => 'avatarExtension',
        'lastPosterAvatarWidth' => 'width',
        'lastPosterAvatarHeight' => 'height',
        'lastPosterEmail' => 'email',
        'lastPosterDisableAvatar' => 'disableAvatar',
        'lastPosterEnableGravatar' => 'enableGravatar',
        'lastPosterGravatarFileExtension' => 'gravatarFileExtension',
        'lastPosterAvatarFileHash' => 'fileHash',
    ];

    /**
     * @inheritDoc
     * @deprecated
     */
    public function __get($name)
    {
        $value = parent::__get($name);
        if ($value !== null) {
            return $value;
        } elseif (\array_key_exists($name, $this->object->data)) {
            return;
        }

        /** @noinspection PhpVariableVariableInspection */
        $value = $this->getUserProfile()->{$name};
        if ($value !== null) {
            return $value;
        }

        if (isset(static::$__lastUserAvatarPropertyMapping[$name])) {
            return $this->getLastPosterProfile()->getAvatar()->{static::$__lastUserAvatarPropertyMapping[$name]};
        }
    }

    /**
     * Returns the user profile object.
     *
     * @return  UserProfile
     */
    public function getUserProfile()
    {
        if ($this->userProfile === null) {
            if ($this->userID) {
                $this->userProfile = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
            } else {
                $this->userProfile = UserProfile::getGuestUserProfile($this->username);
            }
        }

        return $this->userProfile;
    }

    /**
     * Returns the last poster's profile object.
     *
     * @return  UserProfile
     */
    public function getLastPosterProfile()
    {
        if ($this->lastPosterProfile === null) {
            if ($this->lastPosterID) {
                $this->lastPosterProfile = UserProfileRuntimeCache::getInstance()->getObject($this->lastPosterID);
            } else {
                $this->lastPosterProfile = UserProfile::getGuestUserProfile($this->lastPoster);
            }
        }

        return $this->lastPosterProfile;
    }

    /**
     * Returns the number of pages in this conversation.
     *
     * @return  integer
     */
    public function getPages()
    {
        /** @noinspection PhpUndefinedFieldInspection */
        if (WCF::getUser()->conversationMessagesPerPage) {
            /** @noinspection PhpUndefinedFieldInspection */
            $messagesPerPage = WCF::getUser()->conversationMessagesPerPage;
        } else {
            $messagesPerPage = CONVERSATION_MESSAGES_PER_PAGE;
        }

        return \intval(\ceil(($this->replies + 1) / $messagesPerPage));
    }

    /**
     * Returns a summary of the participants.
     *
     * @return  User[]
     */
    public function getParticipantSummary()
    {
        if ($this->__participantSummary === null) {
            $this->__participantSummary = [];

            if ($this->participantSummary) {
                $data = \unserialize($this->participantSummary);
                if ($data !== false) {
                    foreach ($data as $userData) {
                        $this->__participantSummary[] = new User(null, [
                            'userID' => $userData['userID'],
                            'username' => $userData['username'],
                            'hideConversation' => $userData['hideConversation'],
                        ]);
                    }
                }
            }
        }

        return $this->__participantSummary;
    }

    /**
     * Returns the other participant's profile object.
     *
     * @return  UserProfile
     */
    public function getOtherParticipantProfile()
    {
        if ($this->otherParticipantProfile === null) {
            if ($this->otherParticipantID) {
                $this->otherParticipantProfile = UserProfileRuntimeCache::getInstance()
                    ->getObject($this->otherParticipantID);
            } else {
                $this->otherParticipantProfile = UserProfile::getGuestUserProfile($this->otherParticipant);
            }
        }

        return $this->otherParticipantProfile;
    }

    /**
     * Assigns a label.
     *
     * @param ConversationLabel $label
     */
    public function assignLabel(ConversationLabel $label)
    {
        $this->labels[$label->labelID] = $label;
    }

    /**
     * Returns a list of assigned labels.
     *
     * @return  ConversationLabel[]
     */
    public function getAssignedLabels()
    {
        return $this->labels;
    }

    /**
     * Converts a conversation into a viewable conversation.
     *
     * @param Conversation $conversation
     * @param ConversationLabelList $labelList
     * @return  ViewableConversation
     */
    public static function getViewableConversation(Conversation $conversation, ?ConversationLabelList $labelList = null)
    {
        $conversation = new self($conversation);

        if ($labelList === null) {
            $labelList = ConversationLabel::getLabelsByUser();
        }

        $labels = $labelList->getObjects();
        if (!empty($labels)) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("conversationID = ?", [$conversation->conversationID]);
            $conditions->add("labelID IN (?)", [\array_keys($labels)]);

            $sql = "SELECT	labelID
				FROM	wcf" . WCF_N . "_conversation_label_to_object
				" . $conditions;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditions->getParameters());
            while ($row = $statement->fetchArray()) {
                $conversation->assignLabel($labels[$row['labelID']]);
            }
        }

        return $conversation;
    }
}
