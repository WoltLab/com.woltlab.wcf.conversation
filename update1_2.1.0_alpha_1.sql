ALTER TABLE wcf1_conversation_message ADD lastEditTime INT(10) NOT NULL DEFAULT 0;
ALTER TABLE wcf1_conversation_message ADD editCount MEDIUMINT(7) NOT NULL DEFAULT 0;
ALTER TABLE wcf1_conversation_message ADD hasEmbeddedObjects TINYINT(1) NOT NULL DEFAULT 0;
