/**
 * Provides the editor for conversation subjects.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module	WoltLabSuite/Core/Conversation/Ui/Subject/Editor
 */
define(['Ajax', 'EventKey', 'Language', 'Ui/Dialog', 'Ui/Notification'], function (Ajax, EventKey, Language, UiDialog, UiNotification) {
	"use strict";
	
	var _objectId = 0;
	var _subject = null;
	
	/**
	 * @exports     WoltLabSuite/Core/Conversation/Ui/Subject/Editor
	 */
	return {
		/**
		 * Shows the edit dialog for the selected conversation's subject.
		 * 
		 * @param       {int}           objectId
		 */
		beginEdit: function (objectId) {
			_objectId = objectId;
			
			UiDialog.open(this);
		},
		
		/**
		 * Validates and saves the new subject.
		 * 
		 * @param       {Event}         event
		 * @protected
		 */
		_saveEdit: function (event) {
			event.preventDefault();
			
			var innerError = _subject.nextElementSibling;
			if (innerError && innerError.classList.contains('innerError')) {
				elRemove(innerError);
			}
			
			var value = _subject.value.trim();
			if (value === '') {
				innerError = elCreate('small');
				innerError.className = 'innerError';
				innerError.textContent = Language.get('wcf.global.form.error.empty');
				_subject.parentNode.insertBefore(innerError, _subject.nextElementSibling);
			}
			else {
				Ajax.api(this, {
					parameters: {
						subject: value
					},
					objectIDs: [_objectId]
				});
			}
		},
		
		/**
		 * Retrieves the current conversation subject.
		 * 
		 * @return      {string}
		 * @protected
		 */
		_getCurrentValue: function () {
			var value = '';
			elBySelAll('.jsConversationSubject[data-conversation-id="' + _objectId + '"], .conversationLink[data-object-id="' + _objectId + '"]', undefined, function (subject) {
				value = subject.textContent;
			});
			
			return value;
		},
		
		_ajaxSuccess: function (data) {
			UiDialog.close(this);
			
			elBySelAll('.jsConversationSubject[data-conversation-id="' + _objectId + '"], .conversationLink[data-object-id="' + _objectId + '"]', undefined, function (subject) {
				subject.textContent = data.returnValues.subject;
			});
			
			UiNotification.show();
		},
		
		_dialogSetup: function () {
			return {
				id: 'dialogConversationSubjectEditor',
				options: {
					onSetup: (function (content) {
						_subject = elById('jsConversationSubject');
						_subject.addEventListener('keyup', (function (event) {
							if (EventKey.Enter(event)) {
								this._saveEdit(event);
							}
						}).bind(this));
						
						elBySel('.jsButtonSave', content).addEventListener(WCF_CLICK_EVENT, this._saveEdit.bind(this));
					}).bind(this),
					onShow: (function () {
						_subject.value = this._getCurrentValue();
					}).bind(this),
					title: Language.get('wcf.conversation.edit.subject')
				},
				source: '<dl>'
					+ '<dt><label for="jsConversationSubject">' + Language.get('wcf.global.subject') + '</label></dt>'
					+ '<dd><input type="text" id="jsConversationSubject" class="long" maxlength="255"></dd>'
				+ '</dl>'
				+ '<div class="formSubmit"><button class="buttonPrimary jsButtonSave">' + Language.get('wcf.global.button.save') + '</button></div>'
			};
		},
		
		_ajaxSetup: function () {
			return {
				data: {
					actionName: 'editSubject',
					className: 'wcf\\data\\conversation\\ConversationAction'
				}
			}
		}
	};
});
