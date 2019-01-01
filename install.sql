DROP TABLE IF EXISTS wcf1_conversation;
CREATE TABLE wcf1_conversation (
	conversationID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	subject VARCHAR(255) NOT NULL DEFAULT '',
	time INT(10) NOT NULL DEFAULT 0,
	firstMessageID INT(10),
	userID INT(10),
	username VARCHAR(255) NOT NULL DEFAULT '',
	lastPostTime INT(10) NOT NULL DEFAULT 0,
	lastPosterID INT(10),
	lastPoster VARCHAR(255) NOT NULL DEFAULT '',
	replies MEDIUMINT(7) NOT NULL DEFAULT 0,
	attachments SMALLINT(5) NOT NULL DEFAULT 0,
	participants MEDIUMINT(7) NOT NULL DEFAULT 0,
	participantSummary TEXT,
	participantCanInvite TINYINT(1) NOT NULL DEFAULT 0,
	isClosed TINYINT(1) NOT NULL DEFAULT 0,
	isDraft TINYINT(1) NOT NULL DEFAULT 0,
	draftData MEDIUMTEXT,
	
	KEY (userID, isDraft)
);

DROP TABLE IF EXISTS wcf1_conversation_to_user;
CREATE TABLE wcf1_conversation_to_user (
	conversationID INT(10) NOT NULL,
	participantID INT(10),
	username VARCHAR(255) NOT NULL DEFAULT '',
	hideConversation TINYINT(1) NOT NULL DEFAULT 0,
	isInvisible TINYINT(1) NOT NULL DEFAULT 0,
	lastVisitTime INT(10) NOT NULL DEFAULT 0,
	joinedAt INT(10) NOT NULL DEFAULT 0,
	leftAt INT(10) NOT NULL DEFAULT 0,
	lastMessageID INT(10) NULL,
	
	UNIQUE KEY (participantID, conversationID),
	KEY (participantID, hideConversation)
);

DROP TABLE IF EXISTS wcf1_conversation_message;
CREATE TABLE wcf1_conversation_message (
	messageID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	conversationID INT(10) NOT NULL,
	userID INT(10),
	username VARCHAR(255) NOT NULL DEFAULT '',
	message MEDIUMTEXT NOT NULL,
	time INT(10) NOT NULL DEFAULT 0,
	attachments SMALLINT(5) NOT NULL DEFAULT 0,
	enableHtml TINYINT(1) NOT NULL DEFAULT 0,
	ipAddress VARCHAR(39) NOT NULL DEFAULT '',
	lastEditTime INT(10) NOT NULL DEFAULT 0,
	editCount MEDIUMINT(7) NOT NULL DEFAULT 0,
	hasEmbeddedObjects TINYINT(1) NOT NULL DEFAULT 0,
	
	KEY (conversationID, userID),
	KEY (ipAddress)
);

-- labels
DROP TABLE IF EXISTS wcf1_conversation_label;
CREATE TABLE wcf1_conversation_label (
	labelID INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	userID INT(10) NOT NULL,
	label VARCHAR(80) NOT NULL DEFAULT '',
	cssClassName VARCHAR(255) NOT NULL DEFAULT ''
);

DROP TABLE IF EXISTS wcf1_conversation_label_to_object;
CREATE TABLE wcf1_conversation_label_to_object (
	labelID INT(10) NOT NULL,
	conversationID INT(10) NOT NULL,
	
	UNIQUE KEY (labelID, conversationID)
);

ALTER TABLE wcf1_user_group ADD canBeAddedAsParticipant TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE wcf1_conversation ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_conversation ADD FOREIGN KEY (lastPosterID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_conversation ADD FOREIGN KEY (firstMessageID) REFERENCES wcf1_conversation_message (messageID) ON DELETE SET NULL;

ALTER TABLE wcf1_conversation_to_user ADD FOREIGN KEY (conversationID) REFERENCES wcf1_conversation (conversationID) ON DELETE CASCADE;
ALTER TABLE wcf1_conversation_to_user ADD FOREIGN KEY (participantID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;
ALTER TABLE wcf1_conversation_to_user ADD FOREIGN KEY (lastMessageID) REFERENCES wcf1_conversation_message (messageID) ON DELETE SET NULL;

ALTER TABLE wcf1_conversation_message ADD FOREIGN KEY (conversationID) REFERENCES wcf1_conversation (conversationID) ON DELETE CASCADE;
ALTER TABLE wcf1_conversation_message ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE SET NULL;

ALTER TABLE wcf1_conversation_label ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;

ALTER TABLE wcf1_conversation_label_to_object ADD FOREIGN KEY (labelID) REFERENCES wcf1_conversation_label (labelID) ON DELETE CASCADE;
ALTER TABLE wcf1_conversation_label_to_object ADD FOREIGN KEY (conversationID) REFERENCES wcf1_conversation (conversationID) ON DELETE CASCADE;
