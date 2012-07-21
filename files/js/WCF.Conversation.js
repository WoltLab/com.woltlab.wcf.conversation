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
	 */
	init: function() {
		this._conversations = { };
		
		var self = this;
		$('.conversation').each(function(index, conversation) {
			var $conversation = $(conversation);
			var $conversationID = $conversation.data('conversationID');
			
			if (!self._conversations[$conversationID]) {
				self._conversations[$conversationID] = $conversation;
				var $labelIDs = eval($conversation.data('labelIDs'));
				
				// set attributes
				self._attributes[$conversationID] = {
					isClosed: ($conversation.data('isClosed') ? true : false),
					labelIDs: $labelIDs
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
		return (this.getAvailableLabels()).length;
	},
	
	getAvailableLabels: function() {
		var $labels = [ ];
		
		$('#conversationLabelFilter > .dropdownMenu li').each(function(index, listItem) {
			var $listItem = $(listItem);
			if ($listItem.hasClass('dropdownDivider')) {
				return false;
			}
			
			var $span = $listItem.find('span');
			$labels.push({
				cssClassName: $span.data('cssClassName'),
				labelID: $span.data('labelID'),
				label: $span.text()
			});
		});
		
		return $labels;
	},
	
	/**
	 * Updates conversation data.
	 * 
	 * @param	integer		conversationID
	 * @param	object		data
	 */
	update: function(conversationID, key, data) {
		if (!this._conversations[conversationID]) {
			console.debug("[WCF.Conversation.EditorHandler] Unknown conversation id '" + conversationID + "'");
			return;
		}
		var $conversation = this._conversations[conversationID];
		
		switch (key) {
			case 'labelIDs':
				var $labels = { };
				$('#conversationLabelFilter > .dropdownMenu > li > a > span').each(function(index, span) {
					var $span = $(span);
					
					$labels[$span.data('labelID')] = {
						cssClassName: $span.data('cssClassName'),
						label: $span.text()
					};
				});
				
				var $labelList = $conversation.find('.columnTopic > h1 > .labelList');
				if (!data.length) {
					if ($labelList.length) $labelList.remove();
				}
				else {
					// create label list if missing
					if (!$labelList.length) {
						$labelList = $('<ul class="labelList" />').prependTo($conversation.find('.columnTopic > h1'));
					}
					
					// remove all existing labels
					$labelList.empty();
					
					// insert labels
					for (var $i = 0, $length = data.length; $i < $length; $i++) {
						var $label = $labels[data[$i]];
						$('<li><span class="badge label' + ($label.cssClassName ? " " + $label.cssClassName : "") + '">' + $label.label + '</span>&nbsp;</li>').appendTo($labelList);
					}
				}
			break;
		}
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
	},
	
	/**
	 * @see	WCF.InlineEditor._execute()
	 */
	_execute: function(elementID, optionName) {
		// abort if option is invalid or not accessible
		if (!this._validate(elementID, optionName)) {
			return false;
		}
		
		switch (optionName) {
			case 'assignLabel':
				new WCF.Conversation.Label.Editor(this._editorHandler, elementID);
			break;
		}
	}
});

/**
 * Namespace for label-related classes.
 */
WCF.Conversation.Label = { };

/**
 * Providers an editor for conversation labels.
 * 
 * @param	WCF.Conversation.EditorHandler	editorHandler
 * @param	string				elementID
 * @param	array<integer>			conversationIDs
 */
WCF.Conversation.Label.Editor = Class.extend({
	/**
	 * list of conversation id
	 * @var	array<integer>
	 */
	_conversationIDs: 0,
	
	/**
	 * dialog object
	 * @var	jQuery
	 */
	_dialog: null,
	
	/**
	 * editor handler object
	 * @var	WCF.Conversation.EditorHandler
	 */
	_editorHandler: null,
	
	/**
	 * system notification object
	 * @var	WCF.System.Notification
	 */
	_notification: null,
	
	/**
	 * action proxy object
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the label editor for given conversation.
	 * 
	 * @param	WCF.Conversation.EditorHandler	editorHandler
	 * @param	string				elementID
	 * @param	array<integer>			conversationIDs
	 */
	init: function(editorHandler, elementID, conversationIDs) {
		if (elementID) {
			this._conversationIDs = [ $('#' + elementID).data('conversationID') ];
		}
		else {
			this._conversationIDs = conversationIDs;
		}
		
		this._dialog = null;
		this._editorHandler = editorHandler;
		
		this._notification = new WCF.System.Notification(WCF.Language.get('wcf.conversation.label.management.addLabel.success'));
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		this._loadDialog();
	},
	
	/**
	 * Loads label assignment dialog.
	 */
	_loadDialog: function() {
		this._proxy.setOption('data', {
			actionName: 'getLabelForm',
			className: 'wcf\\data\\conversation\\label\\ConversationLabelAction',
			parameters: {
				conversationIDs: this._conversationIDs
			}
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
		switch (data.returnValues.actionName) {
			case 'assignLabel':
				this._assignLabels(data);
			break;
			
			case 'getLabelForm':
				this._renderDialog(data);
			break;
		}
	},
	
	/**
	 * Renders the label assignment form overlay.
	 * 
	 * @param	object		data
	 */
	_renderDialog: function(data) {
		if (this._dialog === null) {
			this._dialog = $('#conversationLabelForm');
			if (!this._dialog.length) {
				this._dialog = $('<div id="conversationLabelForm" />').hide().appendTo(document.body);
			}
		}
		
		this._dialog.html(data.returnValues.template);
		this._dialog.wcfDialog({
			title: WCF.Language.get('wcf.conversation.label.assignLabels')
		});
		this._dialog.wcfDialog('render');
		
		$('#assignLabels').click($.proxy(this._save, this));
	},
	
	/**
	 * Saves label assignments for current conversation id.
	 */
	_save: function() {
		var $labelIDs = [ ];
		this._dialog.find('input').each(function(index, checkbox) {
			var $checkbox = $(checkbox);
			if ($checkbox.is(':checked')) {
				$labelIDs.push($checkbox.data('labelID'));
			}
		});
		
		this._proxy.setOption('data', {
			actionName: 'assignLabel',
			className: 'wcf\\data\\conversation\\label\\ConversationLabelAction',
			parameters: {
				conversationIDs: this._conversationIDs,
				labelIDs: $labelIDs
			}
		});
		this._proxy.sendRequest();
	},
	
	/**
	 * Updates conversation labels.
	 * 
	 * @param	object		data
	 */
	_assignLabels: function(data) {
		// update conversation
		for (var $i = 0, $length = this._conversationIDs; $i < $length; $i++) {
			this._editorHandler.update(this._conversationIDs[$i], 'labelIDs', data.returnValues.labelIDs);
		}
		
		// close dialog and show a 'success' notice
		this._dialog.wcfDialog('close');
		this._notification.show();
	}
});

/**
 * Label manager for conversations.
 * 
 * @param	string		link
 */
WCF.Conversation.Label.Manager = Class.extend({
	/**
	 * deleted label id
	 * @var	integer
	 */
	_deletedLabelID: 0,
	
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
	 * system notification object
	 * @var	WCF.System.Notification
	 */
	_notification: '',
	
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
		this._deletedLabelID = 0;
		this._link = link;
		
		this._labels = $('#conversationLabelFilter .dropdownMenu');
		$('#manageLabel').click($.proxy(this._click, this));
		
		this._notification = new WCF.System.Notification(WCF.Language.get('wcf.conversation.label.management.addLabel.success'));
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
		
		if (data.returnValues && data.returnValues.actionName) {
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
		}
		else {
			// check if delete label id is present within URL (causing an IllegalLinkException if reloading)
			if (this._deletedLabelID) {
				var $regex = new RegExp('(\\?|&)labelID=' + this._deletedLabelID);
				window.location = window.location.toString().replace($regex, '');
			}
			else {
				// reload page
				window.location.reload();
			}
		}
	},
	
	/**
	 * Inserts a previously created label.
	 * 
	 * @param	object		data
	 */
	_insertLabel: function(data) {
		var $listItem = $('<li><a href="' + this._link + '&labelID=' + data.returnValues.labelID + '"><span class="badge label' + (data.returnValues.cssClassName ? ' ' + data.returnValues.cssClassName : '') + '">' + data.returnValues.label + '</span></a></li>');
		$listItem.children('a').data('labelID', data.returnValues.labelID);
		
		var $divider = this._labels.find('.dropdownDivider:eq(0)').show();
		$listItem.insertBefore($divider);
		
		this._notification.show();
	},
	
	/**
	 * Binds event listener for label management.
	 */
	_bindListener: function() {
		$('#labelName').keyup($.proxy(this._updateLabels, this));
		$('#addLabel').disable().click($.proxy(this._addLabel, this));
		$('#editLabel').disable();
		
		this._dialog.find('.conversationLabelList a.label').click($.proxy(this._edit, this));
	},
	
	/**
	 * Prepares a label for editing.
	 * 
	 * @param	object		event
	 */
	_edit: function(event) {
		var $label = $(event.currentTarget);
		
		// replace legends
		var $legend = WCF.Language.get('wcf.conversation.label.management.editLabel').replace(/#labelName#/, $label.text());
		$('#conversationLabelManagementForm').data('labelID', $label.data('labelID')).children('legend').text($legend);
		
		// update text input
		$('#labelName').val($label.text()).trigger('keyup');
		
		// select css class name
		var $cssClassName = $label.data('cssClassName');
		$('#labelManagementList input').each(function(index, input) {
			var $input = $(input);
			
			if ($input.val() == $cssClassName) {
				$input.attr('checked', 'checked');
			}
		});
		
		// toggle buttons
		$('#addLabel').hide();
		$('#editLabel').show().click($.proxy(this._editLabel, this));
		$('#deleteLabel').show().click($.proxy(this._deleteLabel, this));
	},
	
	/**
	 * Edits a label.
	 */
	_editLabel: function() {
		this._proxy.setOption('data', {
			actionName: 'update',
			className: 'wcf\\data\\conversation\\label\\ConversationLabelAction',
			objectIDs: [ $('#conversationLabelManagementForm').data('labelID') ],
			parameters: {
				data: {
					cssClassName: $('#labelManagementList input:checked').val(),
					label: $('#labelName').val()
				}
			}
		});
		this._proxy.sendRequest();
	},
	
	/**
	 * Deletes a label.
	 */
	_deleteLabel: function() {
		var $title = WCF.Language.get('wcf.conversation.label.management.deleteLabel.confirmMessage').replace(/#labelName#/, $('#labelName').val());
		WCF.System.Confirmation.show($title, $.proxy(function(action) {
			if (action === 'confirm') {
				this._proxy.setOption('data', {
					actionName: 'delete',
					className: 'wcf\\data\\conversation\\label\\ConversationLabelAction',
					objectIDs: [ $('#conversationLabelManagementForm').data('labelID') ]
				});
				this._proxy.sendRequest();
				
				this._deletedLabelID = $('#conversationLabelManagementForm').data('labelID');
			}
		}, this));
	},
	
	/**
	 * Updates label text within label management.
	 */
	_updateLabels: function() {
		var $value = $('#labelName').val();
		if ($value) {
			$('#addLabel, #editLabel').enable();
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
		var $cssClassName = $('#labelManagementList input:checked').val();
		
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
		
		// close dialog
		this._dialog.wcfDialog('close');
	}
});
