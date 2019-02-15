/**
 * Adds participants to an existing conversation.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module	WoltLabSuite/Core/Conversation/Ui/Participant/Add
 */
define(['Ajax', 'Language', 'Ui/Dialog', 'Ui/Notification', 'WoltLabSuite/Core/Ui/ItemList/User'], function(Ajax, Language, UiDialog, UiNotification, UiItemListUser) {
	"use strict";
	
	/**
	 * @constructor
	 * @param	{int}   conversationId		conversation id
	 */
	function UiParticipantAdd(conversationId) { this.init(conversationId); }
	UiParticipantAdd.prototype = {
		/**
		 * Manages the form to add one or more participants to an existing conversation.
		 * 
		 * @param	{int}   conversationId          conversation id
		 */
		init: function(conversationId) {
			this._conversationId = conversationId;
			
			Ajax.api(this, {
				actionName: 'getAddParticipantsForm'
			});
		},
		
		_ajaxSetup: function() {
			return {
				data: {
					className: 'wcf\\data\\conversation\\ConversationAction',
					objectIDs: [ this._conversationId ]
				}
			};
		},
		
		/**
		 * Handles successful Ajax requests.
		 * 
		 * @param	{Object}	data		response data
		 */
		_ajaxSuccess: function(data) {
			switch (data.actionName) {
				case 'addParticipants':
					this._handleResponse(data);
					break;
				
				case 'getAddParticipantsForm':
					this._render(data);
					break;
			}
		},
		
		/**
		 * Shows the success message and closes the dialog overlay.
		 * 
		 * @param	{Object}	data		response data
		 */
		_handleResponse: function(data) {
			//noinspection JSUnresolvedVariable
			if (data.returnValues.errorMessage) {
				var innerError = elCreate('small');
				innerError.className = 'innerError';
				//noinspection JSUnresolvedVariable
				innerError.textContent = data.returnValues.errorMessage;
				
				var itemList = elById('participantsInput').closest('.inputItemList');
				itemList.parentNode.insertBefore(innerError, itemList.nextSibling);
				
				var oldError = innerError.nextElementSibling;
				if (oldError && oldError.classList.contains('innerError')) {
					elRemove(oldError);
				}
				
				return;
			}
			
			//noinspection JSUnresolvedVariable
			if (data.returnValues.count) {
				//noinspection JSUnresolvedVariable
				UiNotification.show(data.returnValues.successMessage, window.location.reload.bind(window.location));
			}
			
			UiDialog.close(this);
		},
		
		/**
		 * Renders the dialog to add participants.
		 * 
		 * @param	{object}	data		response data
		 */
		_render: function(data) {
			//noinspection JSUnresolvedVariable
			UiDialog.open(this, data.returnValues.template);
			
			var buttonSubmit = elById('addParticipants');
			buttonSubmit.disabled = true;
			
			//noinspection JSUnresolvedVariable
			UiItemListUser.init('participantsInput', {
				callbackChange: function(elementId, values) { buttonSubmit.disabled = (values.length === 0); },
				excludedSearchValues: data.returnValues.excludedSearchValues,
				maxItems: data.returnValues.maxItems,
				includeUserGroups: data.returnValues.canAddGroupParticipants,
				restrictUserGroupIDs: data.returnValues.restrictUserGroupIDs,
				csvPerType: true
			});
			
			buttonSubmit.addEventListener('click', this._submit.bind(this));
		},
		
		/**
		 * Sends a request to add participants.
		 */
		_submit: function() {
			var values = UiItemListUser.getValues('participantsInput'), participants = [], participantsGroupIDs = [];
			for (var i = 0, length = values.length; i < length; i++) {
				if (values[i].type === 'group') participantsGroupIDs.push(values[i].objectId);
				else participants.push(values[i].value);
			}
			
			var parameters = {
				participants: participants,
				participantsGroupIDs: participantsGroupIDs
			};
			
			var visibility = elBySel('input[name="messageVisibility"]:checked, input[name="messageVisibility"][type="hidden"]', UiDialog.getDialog(this).content);
			if (visibility) parameters.visibility = visibility.value;
			
			Ajax.api(this, {
				actionName: 'addParticipants',
				parameters: parameters
			});
		},
		
		_dialogSetup: function() {
			return {
				id: 'conversationAddParticipants',
				options: {
					title: Language.get('wcf.conversation.edit.addParticipants')
				},
				source: null
			};
		}
	};
	
	return UiParticipantAdd;
});
