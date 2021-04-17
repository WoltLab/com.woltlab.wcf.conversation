<?php

use wcf\data\language\item\LanguageItemAction;
use wcf\data\language\item\LanguageItemList;

$languageItems = [
    'wcf.acp.group.option.user.conversation.allowedAttachmentExtensions.description',
];

$languageItemList = new LanguageItemList();
$languageItemList->getConditionBuilder()->add('languageItem IN (?)', [$languageItems]);
$languageItemList->readObjects();

(new LanguageItemAction($languageItemList->getObjects(), 'delete'))->executeAction();
