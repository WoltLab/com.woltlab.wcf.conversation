<?php
namespace wcf\data\conversation\label;
use wcf\data\DatabaseObject;
use wcf\system\WCF;

/**
 * Represents a conversation label.
 *
 * @author	Marcel Werk
 * @copyright	2009-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.conversation
 * @subpackage	data.conversation.label
 * @category 	Community Framework
 */
class ConversationLabel extends DatabaseObject {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'conversation_label';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'labelID';
	
	/**
	 * list of pre-defined css class names
	 * @var	array<string>
	 */
	public static $availableCssClassNames = array(
			'yellow',
			'orange',
			'brown',
			'red',
			'pink',
			'purple',
			'blue',
			'green',
			'black',
	
			'none' /* not a real value */
	);
	
	/**
	 * Returns a list of conversation labels for given user id.
	 * 
	 * @param	integer		$userID
	 * @return	wcf\data\conversation\label\ConversationLabelList
	 */
	public static function getLabelsByUser($userID = null) {
		if ($userID === null) $userID = WCF::getUser()->userID;
		
		$labelList = new ConversationLabelList();
		$labelList->getConditionBuilder()->add("conversation_label.userID = ?", array($userID));
		$labelList->sqlLimit = 0;
		$labelList->readObjects();
		
		return $labelList;
	}
	
	/**
	 * Returns a list of available CSS class names.
	 * 
	 * @return	array<string>
	 */
	public static function getLabelCssClassNames() {
		return self::$availableCssClassNames;
	}
}
