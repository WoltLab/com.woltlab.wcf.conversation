/**
 * Namespace for conversations.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2011 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Conversation = { };

/**
 * Core editor handler for conversations.
 * 
 * @param	object		availableLabels
 */
WCF.Conversation.EditorHandler = Class.extend({
	/**
	 * list of available labels
	 * @var	object
	 */
	_availableLabels: { },
	
	/**
	 * list of attributes per conversation
	 * @var	object
	 */
	_attributes: { },
	
	/**
	 * list of conversations
	 * @var	object
	 */
	_conversations: { },
	
	/**
	 * list of permissions per conversation
	 * @var	object
	 */
	_permissions: { },
	
	/**
	 * Initializes the core editor handler for conversations.
	 * 
	 * @param	object		availableLabels
	 */
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
	
	/**
	 * Returns a permission's value for given conversation id.
	 * 
	 * @param	integer		conversationID
	 * @param	string		permission
	 * @return	boolean
	 */
	getPermission: function(conversationID, permission) {
		if (this._permissions[conversationID][permission] === undefined) {
			return false;
		}
		
		return (this._permissions[conversationID][permission]) ? true : false;
	},
	
	/**
	 * Returns an attribute's value for given conversation id.
	 * 
	 * @param	integer		conversationID
	 * @param	string		key
	 * @return	mixed
	 */
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
	
	/**
	 * Counts available labels.
	 * 
	 * @return	integer
	 */
	countAvailableLabels: function() {
		return $.getLength(this._availableLabels);
	}
});

/**
 * Inline editor implementation for conversations.
 * 
 * @see	WCF.Inline.Editor
 */
WCF.Conversation.InlineEditor = WCF.InlineEditor.extend({
	/**
	 * editor handler object
	 * @var	WCF.Conversation.EditorHandler
	 */
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
	
	/**
	 * Sets editor handler object.
	 * 
	 * @param	WCF.Conversation.EditorHandler	editorHandler
	 */
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

/**
 * Label manager for conversations.
 * 
 * @param	string		link
 */
WCF.Conversation.LabelManager = Class.extend({
	/**
	 * dialog object
	 * @var	jQuery
	 */
	_dialog: null,
	
	/**
	 * list of labels
	 * @var	jQuery
	 */
	_labels: null,
	
	/**
	 * parsed label link
	 * @var	string
	 */
	_link: '',
	
	/**
	 * action proxy object
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the label manager for conversations.
	 * 
	 * @param	string		link
	 */
	init: function(link) {
		this._link = link;
		
		this._labels = $('#labelList');
		$('#manageLabel').click($.proxy(this._click, this));
		
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
	},
	
	/**
	 * Handles clicks on the 'manage labels' button.
	 */
	_click: function() {
		this._proxy.setOption('data', {
			actionName: 'getLabelManagement',
			className: 'wcf\\data\\conversation\\ConversationAction'
		});
		this._proxy.sendRequest();
	},
	
	/**
	 * Handles successful AJAX requests.
	 * 
	 * @param	object		data
	 * @param	string		textStatus
	 * @param	jQuery		jqXHR
	 */
	_success: function(data, textStatus, jqXHR) {
		if (this._dialog === null) {
			this._dialog = $('<div id="labelManagement" />').hide().appendTo(document.body);
		}
		
		switch (data.returnValues.actionName) {
			case 'add':
				this._insertLabel(data);
			break;
			
			case 'getLabelManagement':
				// render dialog
				this._dialog.empty().html(data.returnValues.template);
				this._dialog.wcfDialog({
					title: WCF.Language.get('wcf.conversation.label.management')
				});
				this._dialog.wcfDialog('render');
				
				// bind action listeners
				this._bindListener();
			break;
		}
	},
	
	/**
	 * Inserts a previously created label.
	 * 
	 * @param	object		data
	 */
	_insertLabel(data) {
		var $listItem = $('<li><a href="' + this._link + '&labelID=' + data.returnValues.labelID + '" class="badge label' + (if data.returnValues.cssClassName ? ' ' + data.returnValues.cssClassName : '') + '">' + data.returnValues.label + '</a></li>');
		$listItem.children('a').data('labelID', data.returnValues.labelID);
		
		$listItem.appendTo(this._labels);
	},
	
	/**
	 * Binds event listener for label management.
	 */
	_bindListener: function() {
		$('#labelName').keyup($.proxy(this._updateLabels, this));
		$('#addLabel').disable().click($.proxy(this._addLabel, this));
	},
	
	/**
	 * Updates label text within label management.
	 */
	_updateLabels: function() {
		var $value = $('#labelName').val();
		if ($value) {
			$('#addLabel').enable();
		}
		else {
			$value = WCF.Language.get('wcf.conversation.label.placeholder');
		}
		
		$('#labelManagementList').find('span.label').text($value);
	},
	
	/**
	 * Sends an AJAX request to add a new label.
	 */
	_addLabel: function() {
		var $labelName = $('#labelName').val();
		var $cssClassName = $('#labelManagementList').find('input:checked').val();
		
		this._proxy.setOption('data', {
			actionName: 'add',
			className: 'wcf\\data\\conversation\\label\\ConversationLabelAction',
			parameters: {
				data: {
					cssClassName: $cssClassName,
					labelName: $labelName
				}
			}
		});
		this._proxy.sendRequest();
	}
});
