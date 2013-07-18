/* a9bfc5b */
ALTER TABLE wcf1_conversation_to_user ADD username VARCHAR(255) NOT NULL DEFAULT '';

/* 7c5aa43 */
ALTER TABLE wcf1_conversation_label CHANGE cssClassName cssClassName VARCHAR(255) NOT NULL DEFAULT '';