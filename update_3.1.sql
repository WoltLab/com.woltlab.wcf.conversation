ALTER TABLE wcf1_conversation_to_user ADD FOREIGN KEY (lastMessageID) REFERENCES wcf1_conversation_message (messageID) ON DELETE SET NULL;
