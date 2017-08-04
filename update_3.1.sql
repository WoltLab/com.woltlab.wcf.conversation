ALTER TABLE wcf1_conversation_to_user ADD COLUMN joinedAt INT(10) NOT NULL DEFAULT 0;
ALTER TABLE wcf1_conversation_to_user ADD COLUMN leftAt INT(10) NOT NULL DEFAULT 0;
ALTER TABLE wcf1_conversation_to_user ADD COLUMN lastMessageID INT(10) NULL;

ALTER TABLE wcf1_conversation_to_user ADD FOREIGN KEY (lastMessageID) REFERENCES wcf1_conversation_message (messageID) ON DELETE SET NULL;
