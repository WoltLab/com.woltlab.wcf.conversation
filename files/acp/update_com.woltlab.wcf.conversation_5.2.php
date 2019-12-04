<?php
use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\DefaultTrueBooleanDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\DatabaseTableChangeProcessor;
use wcf\system\package\plugin\ScriptPackageInstallationPlugin;
use wcf\system\WCF;

/**
 * @author      Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

$tables = [
	DatabaseTable::create('wcf1_conversation_to_user')
		->columns([
			DefaultTrueBooleanDatabaseTableColumn::create('leftByOwnChoice')
		]),
	
	DatabaseTable::create('wcf1_user_group')
		->columns([
			DefaultFalseBooleanDatabaseTableColumn::create('canBeAddedAsConversationParticipant'),
		]),
];

(new DatabaseTableChangeProcessor(
	/** @var ScriptPackageInstallationPlugin $this */
	$this->installation->getPackage(),
	$tables,
	WCF::getDB()->getEditor())
)->process();
