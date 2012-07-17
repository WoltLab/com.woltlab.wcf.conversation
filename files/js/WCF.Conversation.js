/**
 * Namespace for conversations.
 */
WCF.Conversation = { };

WCF.Conversation.EditorHandler = Class.extend({
	_availableLabels: { },
	_attributes: { },
	_conversations: { },
	_permissions: { },
	
	init: function(availableLabels) {
		this._availableLabels = availableLabels || { };
		this._conversations = { };
		
		var self = this;
		$('.conversation').each(function(index, conversation) {
			var $conversation = $(conversation);
			var $conversationID = $conversation.data('conversationID');
			
			if (!self._conversations[$conversationID]) {
				self._conversations[$conversationID] = $conversation;
				
				// set attributes
				self._attributes[$conversationID] = {
					isClosed: ($conversation.data('isClosed') ? true : false)
				};
				
				// set permissions
				self._permissions[$conversationID] = {
					canCloseConversation: ($conversation.data('canCloseConversation') ? true : false)
				};
			}
		});
	},
	
	getPermission: function(conversationID, permission) {
		if (this._permissions[conversationID][permission] === undefined) {
			return false;
		}
		
		return (this._permissions[conversationID][permission]) ? true : false;
	},
	
	getValue: function(conversationID, key) {
		switch (key) {
			case 'labelIDs':
				if (this._attributes[conversationID].labelIDs === undefined) {
					// TODO: fetch label ids
					this._attributes[conversationID].labelIDs = [ ];
				}
				
				return this._attributes[conversationID].labelIDs;
			break;
			
			case 'isClosed':
				return (this._attributes[conversationID].isClosed) ? true : false;
			break;
		}
	},
	
	countAvailableLabels: function() {
		return $.getLength(this._availableLabels);
	}
});

WCF.Conversation.InlineEditor = WCF.InlineEditor.extend({
	_editorHandler: null,
	
	/**
	 * @see	WCF.InlineEditor._setOptions()
	 */
	_setOptions: function() {
		this._options = [
			// isClosed
			{ label: WCF.Language.get('wcf.conversation.edit.close'), optionName: 'close' },
			{ label: WCF.Language.get('wcf.conversation.edit.open'), optionName: 'open' },
			
			// assign labels
			{ label: WCF.Language.get('wcf.conversation.edit.assignLabel'), optionName: 'assignLabel' },
			
			// divider
			{ optionName: 'divider' },
			
			// leave conversation
			{ label: WCF.Language.get('wcf.conversation.edit.leave'), optionName: 'leave' }
		];
	},
	
	setEditorHandler: function(editorHandler) {
		this._editorHandler = editorHandler;
	},
	
	/**
	 * @see	WCF.InlineEditor._getTriggerElement()
	 */
	_getTriggerElement: function(element) {
		return element.find('.jsThreadInlineEditor');
	},
	
	/**
	 * @see	WCF.InlineEditor._validate()
	 */
	_validate: function(elementID, optionName) {
		var $conversationID = $('#' + elementID).data('conversationID');
		
		switch (optionName) {
			case 'assignLabel':
				return (this._editorHandler.countAvailableLabels()) ? true : false;
			break;
			
			case 'close':
			case 'open':
				if (!this._editorHandler.getPermission($conversationID, 'canCloseConversation')) {
					return false;
				}
				
				if (optionName === 'close') return !(this._getEditorHandler().getValue($conversationID, 'isClosed'));
				else return (this._getEditorHandler().getValue($conversationID, 'isClosed'));
			break;
			
			case 'leave':
				return true;
			break;
		}
		
		return false;
	}
});