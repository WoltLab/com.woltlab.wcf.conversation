/**
 * Namespace for conversations.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Conversation = { };

/**
 * Core editor handler for conversations.
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
	init: function(availableLabels) {
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
					canAddParticipants: ($conversation.data('canAddParticipants') ? true : false),
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
	
	/**
	 * Returns a list with the data of the available labels.
	 * 
	 * @return	array<object>
	 */
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
			case 'close':
				$('<li><span class="icon icon16 icon-lock jsTooltip jsIconLock" title="' + WCF.Language.get('wcf.global.state.closed') + '" /></li>').prependTo($conversation.find('.statusIcons'));
				
				this._attributes[conversationID].isClosed = 1;
			break;
			
			case 'labelIDs':
				var $labels = { };
				$('#conversationLabelFilter > .dropdownMenu > li > a > span').each(function(index, span) {
					var $span = $(span);
					
					$labels[$span.data('labelID')] = {
						cssClassName: $span.data('cssClassName'),
						label: $span.text(),
						url: $span.parent().attr('href')
					};
				});
				
				var $labelList = $conversation.find('.columnSubject > .labelList');
				if (!data.length) {
					if ($labelList.length) $labelList.remove();
				}
				else {
					// create label list if missing
					if (!$labelList.length) {
						$labelList = $('<ul class="labelList" />').prependTo($conversation.find('.columnSubject'));
					}
					
					// remove all existing labels
					$labelList.empty();
					
					// insert labels
					for (var $i = 0, $length = data.length; $i < $length; $i++) {
						var $label = $labels[data[$i]];
						$('<li><a href="' + $label.url + '" class="badge label' + ($label.cssClassName ? " " + $label.cssClassName : "") + '">' + WCF.String.escapeHTML($label.label) + '</a>&nbsp;</li>').appendTo($labelList);
					}
				}
			break;
			
			case 'open':
				$conversation.find('.statusIcons li').each(function(index, listItem) {
					var $listItem = $(listItem);
					if ($listItem.children('span.jsIconLock').length) {
						$listItem.remove();
						return false;
					}
				});
				
				this._attributes[conversationID].isClosed = 0;
			break;
		}
	}
});

/**
 * Conversation editor handler for conversation page.
 * 
 * @see	WCF.Conversation.EditorHandler
 * @param	array<object>	availableLabels
 */
WCF.Conversation.EditorHandlerConversation = WCF.Conversation.EditorHandler.extend({
	/**
	 * list of available labels
	 * @var	array<object>
	 */
	_availableLabels: null,
	
	/**
	 * @see	WCF.Conversation.EditorHandler.init()
	 * 
	 * @param	array<object>	availableLabels
	 */
	init: function(availableLabels) {
		this._availableLabels = availableLabels || [ ];
		
		this._super();
	},
	
	/**
	 * @see	WCF.Conversation.EditorHandler.getAvailableLabels()
	 */
	getAvailableLabels: function() {
		return this._availableLabels;
	},
	
	/**
	 * @see	WCF.Conversation.EditorHandler.update()
	 */
	update: function(conversationID, key, data) {
		if (!this._conversations[conversationID]) {
			console.debug("[WCF.Conversation.EditorHandler] Unknown conversation id '" + conversationID + "'");
			return;
		}
		
		switch (key) {
			case 'close':
				$('<span class="icon icon16 icon-lock jsTooltip jsIconLock" title="' + WCF.Language.get('wcf.global.state.closed') + '" />').appendTo($('#content > header > h1'));
				
				this._attributes[conversationID].isClosed = 1;
			break;
			
			case 'labelIDs':
				var $container = $('.conversationHeadline');
				if (!data.length) {
					// remove all labels
					$container.find('ul.labelList').remove();
				}
				else {
					var $labelList = $container.find('ul.labelList');
					if (!$labelList.length) {
						$labelList = $('<ul class="labelList" />').appendTo($container);
					}
					
					// remove existing labels
					$labelList.empty();
					
					// add new labels
					for (var $i = 0, $length = data.length; $i < $length; $i++) {
						var $labelID = data[$i];
						
						for (var $j = 0, $innerLength = this.getAvailableLabels().length; $j < $innerLength; $j++) {
							var $label = this.getAvailableLabels()[$j];
							if ($label.labelID == $labelID) {
								$('<li><span class="label badge' + ($label.cssClassName ? " " + $label.cssClassName : "") + '">' + $label.label + '</span>&nbsp;</li>').appendTo($labelList);
								
								break;
							}
						}
					}
				}
			break;
			
			case 'open':
				$('#content > header span.jsIconLock').remove();
				
				this._attributes[conversationID].isClosed = 0;
			break;
		}
	}
});

/**
 * Provides extended actions for thread clipboard actions.
 */
WCF.Conversation.Clipboard = Class.extend({
	/**
	 * editor handler
	 * @var	WCF.Conversation.EditorHandler
	 */
	_editorHandler: null,
	
	/**
	 * Initializes a new WCF.Conversation.Clipboard object.
	 * 
	 * @param	WCF.Conversation.EditorHandler	editorHandler
	 */
	init: function(editorHandler) {
		this._editorHandler = editorHandler;
		
		// bind listener
		$('.jsClipboardEditor').each($.proxy(function(index, container) {
			var $container = $(container);
			var $types = eval($container.data('types'));
			if (WCF.inArray('com.woltlab.wcf.conversation.conversation', $types)) {
				$container.on('clipboardAction', $.proxy(this._execute, this));
				$container.on('clipboardActionResponse', $.proxy(this._evaluateResponse, this));
				return false;
			}
		}, this));
	},
	
	/**
	 * Handles clipboard actions.
	 * 
	 * @param	object		event
	 * @param	string		type
	 * @param	string		actionName
	 * @param	object		parameters
	 */
	_execute: function(event, type, actionName, parameters) {
		if (type === 'com.woltlab.wcf.conversation.conversation' && actionName === 'com.woltlab.wcf.conversation.conversation.assignLabel') {
			new WCF.Conversation.Label.Editor(this._editorHandler, null, parameters.objectIDs);
		}
	},
	
	/**
	 * Evaluates AJAX responses.
	 * 
	 * @param	object		event
	 * @param	object		data
	 * @param	string		type
	 * @param	string		actionName
	 * @param	object		parameters
	 */
	_evaluateResponse: function(event, data, type, actionName, parameters) {
		if (type !== 'com.woltlab.wcf.conversation.conversation') {
			// ignore unreleated events
			return;
		}
		
		switch (actionName) {
			case 'com.woltlab.wcf.conversation.conversation.leave':
			case 'com.woltlab.wcf.conversation.conversation.leavePermanently':
			case 'com.woltlab.wcf.conversation.conversation.restore':
				window.location.reload();
			break;
			
			case 'com.woltlab.wcf.conversation.conversation.close':
			case 'com.woltlab.wcf.conversation.conversation.open':
				for (var $conversationID in data.returnValues.conversationData) {
					var $data = data.returnValues.conversationData[$conversationID];
					
					this._editorHandler.update($conversationID, ($data.isClosed ? 'close' : 'open'), $data);
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
	 * execution environment
	 * @var	string
	 */
	_environment: 'conversation',
	
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
			
			// add participants
			{ label: WCF.Language.get('wcf.conversation.edit.addParticipants'), optionName: 'addParticipants' },
			
			// leave conversation
			{ label: WCF.Language.get('wcf.conversation.edit.leave'), optionName: 'leave' }
		];
	},
	
	/**
	 * Sets editor handler object.
	 * 
	 * @param	WCF.Conversation.EditorHandler	editorHandler
	 * @param	string				environment
	 */
	setEditorHandler: function(editorHandler, environment) {
		this._editorHandler = editorHandler;
		this._environment = (environment == 'list') ? 'list' : 'conversation';
	},
	
	/**
	 * @see	WCF.InlineEditor._getTriggerElement()
	 */
	_getTriggerElement: function(element) {
		return element.find('.jsConversationInlineEditor');
	},
	
	/**
	 * @see	WCF.InlineEditor._validate()
	 */
	_validate: function(elementID, optionName) {
		var $conversationID = $('#' + elementID).data('conversationID');
		
		switch (optionName) {
			case 'addParticipants':
				return (this._editorHandler.getPermission($conversationID, 'canAddParticipants'));
			break;
			
			case 'assignLabel':
				return (this._editorHandler.countAvailableLabels()) ? true : false;
			break;
			
			case 'close':
			case 'open':
				if (!this._editorHandler.getPermission($conversationID, 'canCloseConversation')) {
					return false;
				}
				
				if (optionName === 'close') return !(this._editorHandler.getValue($conversationID, 'isClosed'));
				else return (this._editorHandler.getValue($conversationID, 'isClosed'));
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
			case 'addParticipants':
				new WCF.Conversation.AddParticipants($('#' + elementID).data('conversationID'));
			break;
			
			case 'assignLabel':
				new WCF.Conversation.Label.Editor(this._editorHandler, elementID);
			break;
			
			case 'close':
			case 'open':
				this._updateConversation(elementID, optionName, { isClosed: (optionName === 'close' ? 1 : 0) });
			break;
			
			case 'leave':
				new WCF.Conversation.Leave([ $('#' + elementID).data('conversationID') ], this._environment);
			break;
		}
	},
	
	/**
	 * Updates conversation properties.
	 * 
	 * @param	string		elementID
	 * @param	string		optionName
	 * @param	object		data
	 */
	_updateConversation: function(elementID, optionName, data) {
		var $conversationID = this._elements[elementID].data('conversationID');
		
		switch (optionName) {
			case 'close':
			case 'open':
				this._updateData.push({
					elementID: elementID,
					optionName: optionName,
					data: data
				});
				
				this._proxy.setOption('data', {
					actionName: optionName,
					className: 'wcf\\data\\conversation\\ConversationAction',
					objectIDs: [ $conversationID ]
				});
				this._proxy.sendRequest();
			break;
		}
	},
	
	/**
	 * @see	WCF.InlineEditor._updateState()
	 */
	_updateState: function() {
		for (var $i = 0, $length = this._updateData.length; $i < $length; $i++) {
			var $data = this._updateData[$i];
			var $conversationID = this._elements[$data.elementID].data('conversationID');
			
			switch ($data.optionName) {
				case 'close':
					this._editorHandler.update($conversationID, 'close', $data.data);
				break;
				
				case 'open':
					this._editorHandler.update($conversationID, 'open', $data.data);
				break;
			}
		}
	}
});

/**
 * Provides a dialog for leaving or restoring conversation.
 * 
 * @param	array<integer>		conversationIDs
 */
WCF.Conversation.Leave = Class.extend({
	/**
	 * list of conversation ids
	 * @var	array<integer>
	 */
	_conversationIDs: [ ],
	
	/**
	 * dialog overlay
	 * @var	jQuery
	 */
	_dialog: null,
	
	/**
	 * environment name
	 * @var	string
	 */
	_environment: '',
	
	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the leave/restore dialog for conversations.
	 * 
	 * @param	array<integer>		conversationIDs
	 * @param	string			environment
	 */
	init: function(conversationIDs, environment) {
		this._conversationIDs = conversationIDs;
		this._dialog = null;
		this._environment = environment;
		
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		this._loadDialog();
	},
	
	/**
	 * Loads the dialog overlay.
	 */
	_loadDialog: function()  {
		this._proxy.setOption('data', {
			actionName: 'getLeaveForm',
			className: 'wcf\\data\\conversation\\ConversationAction',
			objectIDs: this._conversationIDs
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
			case 'getLeaveForm':
				this._showDialog(data);
			break;
			
			case 'hideConversation':
				if (this._environment === 'conversation') {
					window.location = data.returnValues.redirectURL;
				}
				else {
					window.location.reload();
				}
			break;
		}
	},
	
	/**
	 * Displays the leave/restore conversation dialog overlay.
	 * 
	 * @param	object		data
	 */
	_showDialog: function(data) {
		if (this._dialog === null) {
			this._dialog = $('#leaveDialog');
			if (!this._dialog.length) {
				this._dialog = $('<div id="leaveDialog" />').hide().appendTo(document.body);
			}
		}
		
		// render dialog
		this._dialog.html(data.returnValues.template);
		this._dialog.wcfDialog({
			title: WCF.Language.get('wcf.conversation.leave.title')
		});
		
		this._dialog.wcfDialog('render');
		
		// bind event listener
		this._dialog.find('#hideConversation').click($.proxy(this._click, this));
	},
	
	/**
	 * Handles conversation state changes.
	 */
	_click: function() {
		var $input = this._dialog.find('input[type=radio]:checked');
		if ($input.length === 1) {
			this._proxy.setOption('data', {
				actionName: 'hideConversation',
				className: 'wcf\\data\\conversation\\ConversationAction',
				objectIDs: this._conversationIDs,
				parameters: {
					hideConversation: $input.val()
				}
			});
			this._proxy.sendRequest();
		}
	}
});

/**
 * Provides methods to add new participants.
 * 
 * @param	integer		conversationID
 */
WCF.Conversation.AddParticipants = Class.extend({
	/**
	 * conversation id
	 * @var	integer
	 */
	_conversationID: 0,
	
	/**
	 * dialog overlay
	 * @var	jQuery
	 */
	_dialog: null,
	
	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the WCF.Conversation.AddParticipants class
	 * 
	 * @param	integer		conversationID
	 */
	init: function(conversationID) {
		this._conversationID = conversationID;
		
		this._dialog = $('#conversationAddParticipants');
		if (!this._dialog.length) {
			this._dialog = $('<div id="conversationAddParticipants" />').hide().appendTo(document.body);
		}
		
		this._proxy = new WCF.Action.Proxy({
			autoSend: true,
			data: {
				actionName: 'getAddParticipantsForm',
				className: 'wcf\\data\\conversation\\ConversationAction',
				objectIDs: [ this._conversationID ]
			},
			success: $.proxy(this._success, this)
		});
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
			case 'addParticipants':
				if (data.returnValues.errorMessage) {
					this._dialog.find('dl.jsAddParticipants').addClass('formError');
					this._dialog.find('dl.jsAddParticipants > dd small.innerError').remove();
					$('<small class="innerError">' + data.returnValues.errorMessage + '</small>').appendTo(this._dialog.find('dl.jsAddParticipants > dd'));
					return;
				}
				
				if (data.returnValues.count) {
					var $notification = new WCF.System.Notification(data.returnValues.successMessage);
					$notification.show();
				}
				
				this._dialog.find('dl.jsAddParticipants').removeClass('formError').find('small.innerError').remove();
				this._dialog.wcfDialog('close');
			break;
			
			case 'getAddParticipantsForm':
				this._renderForm(data);
			break;
		}
	},
	
	/**
	 * Renders the 'add participants' form.
	 * 
	 * @param	object		data
	 */
	_renderForm: function(data) {
		this._dialog.html(data.returnValues.template);
		var $submitButton = this._dialog.find('#addParticipants').disable().click($.proxy(this._submit, this));
		
		new WCF.Search.User('#participantsInput', null, false, data.returnValues.excludeSearchValues, true);
		
		var $participantsInput = $('#participantsInput');
		$participantsInput.keyup(function() {
			if ($.trim($participantsInput.val()) === '') {
				$submitButton.disable();
			}
			else {
				$submitButton.enable();
			}
		});
		
		this._dialog.wcfDialog({
			title: WCF.Language.get('wcf.conversation.edit.addParticipants')
		});
	},
	
	/**
	 * Submits the form to add new participants.
	 */
	_submit: function() {
		var $participants = $.trim($('#participantsInput').val());
		if ($participants == '') {
			this._dialog.wcfDialog('close');
		}
		
		this._proxy.setOption('data', {
			actionName: 'addParticipants',
			className: 'wcf\\data\\conversation\\ConversationAction',
			objectIDs: [ this._conversationID ],
			parameters: {
				participants: $participants
			}
		});
		this._proxy.sendRequest();
	}
});

/**
 * Provides methods to remove participants from conversations.
 * 
 * @see	WCF.Action.Delete
 */
WCF.Conversation.RemoveParticipant = WCF.Action.Delete.extend({
	/**
	 * conversation id
	 * @var	integer
	 */
	_conversationID: 0,
	
	/**
	 * @see	WCF.Action.Delete.init()
	 */
	init: function(conversationID) {
		this._conversationID = conversationID;
		this._super('wcf\\data\\conversation\\ConversationAction', '.jsParticipant');
	},
	
	/**
	 * @see	WCF.Action.Delete._sendRequest()
	 */
	_sendRequest: function(object) {
		this.proxy.setOption('data', {
			actionName: 'removeParticipant',
			className: this._className,
			objectIDs: [ this._conversationID ],
			parameters: {
				userID: $(object).data('objectID')
			}
		});
		
		this.proxy.sendRequest();
	},
	
	/**
	 * @see	WCF.Action.Delete._success()
	 */
	_success: function(data, textStatus, jqXHR) {
		var $userID = data.returnValues.userID;
		
		for (var $index in this._containers) {
			var $container = $('#' + this._containers[$index]);
			if ($container.find('.jsDeleteButton').data('objectID') == $userID) {
				$container.find('.userLink').addClass('conversationLeft');
				$container.find('.jsDeleteButton').remove();
			}
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
		
		this._notification = new WCF.System.Notification(WCF.Language.get('wcf.global.success.edit'));
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
		for (var $i = 0, $length = this._conversationIDs.length; $i < $length; $i++) {
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
		$listItem.find('a > span').data('labelID', data.returnValues.labelID).data('cssClassName', data.returnValues.cssClassName);
		
		var $divider = this._labels.find('.dropdownDivider:eq(0)').show();
		$listItem.insertBefore($divider);
		
		this._notification.show();
	},
	
	/**
	 * Binds event listener for label management.
	 */
	_bindListener: function() {
		$('#labelName').on('keyup keydown keypress', $.proxy(this._updateLabels, this));
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
		var $legend = WCF.Language.get('wcf.conversation.label.management.editLabel').replace(/#labelName#/, WCF.String.escapeHTML($label.text()));
		$('#conversationLabelManagementForm').data('labelID', $label.data('labelID')).children('legend').html($legend);
		
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
		var $value = $.trim($('#labelName').val());
		if ($value) {
			$('#addLabel, #editLabel').enable();
		}
		else {
			$('#addLabel, #editLabel').disable();
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

/**
 * Provides a flexible conversation preview.
 * 
 * @see	WCF.Popover
 */
WCF.Conversation.Preview = WCF.Popover.extend({
	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * @see	WCF.Popover.init()
	 */
	init: function() {
		this._super('.conversationLink');
		
		// init proxy
		this._proxy = new WCF.Action.Proxy({
			showLoadingOverlay: false
		});
		
		WCF.DOMNodeInsertedHandler.addCallback('WCF.Conversation.Preview', $.proxy(this._initContainers, this));
	},
	
	/**
	 * @see	WCF.Popover._loadContent()
	 */
	_loadContent: function() {
		var $link = $('#' + this._activeElementID);
		
		this._proxy.setOption('data', {
			actionName: 'getMessagePreview',
			className: 'wcf\\data\\conversation\\ConversationAction',
			objectIDs: [ $link.data('conversationID') ]
		});
		
		var $elementID = this._activeElementID;
		var self = this;
		this._proxy.setOption('success', function(data, textStatus, jqXHR) {
			self._insertContent($elementID, data.returnValues.template, true);
		});
		this._proxy.sendRequest();
	}
});

/**
 * Loads conversations for user panel.
 * 
 * @see	WCF.UserPanel
 */
WCF.Conversation.UserPanel = WCF.UserPanel.extend({
	/**
	 * link to conversation add
	 * @var	string
	 */
	_addLink: '',
	
	/**
	 * link to show all conversations
	 * @var	string
	 */
	_showAllLink: '',
	
	/**
	 * @see	WCF.UserPanel.init()
	 */
	init: function(showAllLink, addLink) {
		this._addLink = addLink;
		this._noItems = 'wcf.conversation.noMoreItems';
		this._showAllLink = showAllLink;
		
		this._super('unreadConversations');
	},
	
	/**
	 * @see	WCF.UserPanel._addDefaultItems()
	 */
	_addDefaultItems: function(dropdownMenu) {
		this._addDivider(dropdownMenu);
		$('<li><a href="' + this._showAllLink + '">' + WCF.Language.get('wcf.conversation.showAll') + '</a></li>').appendTo(dropdownMenu);
		this._addDivider(dropdownMenu);
		$('<li><a href="' + this._addLink + '">' + WCF.Language.get('wcf.conversation.add') + '</a></li>').appendTo(dropdownMenu);
	},
	
	/**
	 * @see	WCF.UserPanel._getParameters()
	 */
	_getParameters: function() {
		return {
			actionName: 'getUnreadConversations',
			className: 'wcf\\data\\conversation\\ConversationAction'
		};
	}
});

/**
 * Provides an AJAX-based quick reply for conversations.
 */
WCF.Conversation.QuickReply = WCF.Message.QuickReply.extend({
	/**
	 * @see	WCF.Message.QuickReply.init()
	 */
	init: function(quoteManager) {
		this._super(true, quoteManager);
	},
	
	/**
	 * @see	WCF.Message.QuickReply._getClassName()
	 */
	_getClassName: function() {
		return 'wcf\\data\\conversation\\message\\ConversationMessageAction';
	},
	
	/**
	 * @see	WCF.Message.QuickReply._getObjectID()
	 * @returns
	 */
	_getObjectID: function() {
		return this._container.data('conversationID');
	}
});

/**
 * Marks one conversation as read.
 */
WCF.Conversation.MarkAsRead = Class.extend({
	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the mark as read for conversations.
	 */
	init: function() {
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		$(document).on('dblclick', '.conversationList .new .columnAvatar', $.proxy(this._dblclick, this));
	},
	
	/**
	 * Handles double clicks on avatar.
	 * 
	 * @param	object		event
	 */
	_dblclick: function(event) {
		this._proxy.setOption('data', {
			actionName: 'markAsRead',
			className: 'wcf\\data\\conversation\\ConversationAction',
			objectIDs: [ $(event.currentTarget).parents('tr:eq(0)').data('conversationID') ]
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
		$('.conversationList .new').each(function(index, element) {
			var $element = $(element);
			if (WCF.inArray($element.data('conversationID'), data.objectIDs)) {
				// remove new class
				$element.removeClass('new');
				
				// hide arrows
				$element.find('.firstNewPost').parent().remove();
				
				// remove event
				$element.find('.columnAvatar').off('dblclick');
			}
		});
	}
});

/**
 * Namespace for conversation messages.
 */
WCF.Conversation.Message = { };

/**
 * Provides an inline editor for conversation messages.
 * 
 * @see	WCF.Message.InlineEditor
 */
WCF.Conversation.Message.InlineEditor = WCF.Message.InlineEditor.extend({
	/**
	 * @see	WCF.Message.InlineEditor.init()
	 */
	init: function(containerID) {
		this._super(containerID, true);
	},
	
	/**
	 * @see	WCF.Message.InlineEditor._getClassName()
	 */
	_getClassName: function() {
		return 'wcf\\data\\conversation\\message\\ConversationMessageAction';
	}
});

/**
 * Provides the quote manager for conversation messages.
 * 
 * @param	WCF.Message.Quote.Manager	quoteManager
 * @see		WCF.Message.Quote.Handler
 */
WCF.Conversation.Message.QuoteHandler = WCF.Message.Quote.Handler.extend({
	/**
	 * @see	WCF.Message.QuoteManager.init()
	 */
	init: function(quoteManager) {
		this._super(quoteManager, 'wcf\\data\\conversation\\message\\ConversationMessageAction', 'com.woltlab.wcf.conversation.message', '.message', '.messageBody', '.messageBody > div > div.messageText');
	}
});
