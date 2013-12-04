<?php
use wcf\data\user\group\UserGroup;
use wcf\system\WCF;

/**
 * @author	Matthias Schmidt
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
// set default mod permissions
$group = new UserGroup(5);
if ($group->groupID) {
	$sql = "REPLACE INTO	wcf".WCF_N."_user_group_option_value
				(groupID, optionID, optionValue)
		SELECT		5, optionID, 1
		FROM		wcf".WCF_N."_user_group_option
		WHERE		optionName LIKE 'mod.conversation.%'";
	$statement = WCF::getDB()->prepareStatement($sql);
	$statement->execute();
}

$group = new UserGroup(6);
if ($group->groupID) {
	$sql = "REPLACE INTO	wcf".WCF_N."_user_group_option_value
				(groupID, optionID, optionValue)
		SELECT		6, optionID, 1
		FROM		wcf".WCF_N."_user_group_option
		WHERE		optionName LIKE 'mod.conversation.%'";
	$statement = WCF::getDB()->prepareStatement($sql);
	$statement->execute();
}
