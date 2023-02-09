/**
 * Adds participants to an existing conversation.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Dom/Util", "WoltLabSuite/Core/Ui/Dialog", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Ui/ItemList/User", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, Ajax, Util_1, Dialog_1, UiNotification, UiItemListUser, Language) {
    "use strict";
    Ajax = tslib_1.__importStar(Ajax);
    Util_1 = tslib_1.__importDefault(Util_1);
    Dialog_1 = tslib_1.__importDefault(Dialog_1);
    UiNotification = tslib_1.__importStar(UiNotification);
    UiItemListUser = tslib_1.__importStar(UiItemListUser);
    Language = tslib_1.__importStar(Language);
    class UiParticipantAdd {
        conversationId;
        constructor(conversationId) {
            this.conversationId = conversationId;
            Ajax.api(this, {
                actionName: "getAddParticipantsForm",
            });
        }
        _ajaxSetup() {
            return {
                data: {
                    className: "wcf\\data\\conversation\\ConversationAction",
                    objectIDs: [this.conversationId],
                },
            };
        }
        _ajaxSuccess(data) {
            switch (data.actionName) {
                case "addParticipants":
                    this.handleResponse(data);
                    break;
                case "getAddParticipantsForm":
                    this.render(data);
                    break;
            }
        }
        /**
         * Shows the success message and closes the dialog overlay.
         */
        handleResponse(data) {
            if (data.returnValues.errorMessage) {
                Util_1.default.innerError(document.getElementById("participantsInput").closest(".inputItemList"), data.returnValues.errorMessage);
                return;
            }
            if (data.returnValues.count) {
                UiNotification.show(data.returnValues.successMessage, () => window.location.reload());
            }
            Dialog_1.default.close(this);
        }
        /**
         * Renders the dialog to add participants.
         * @protected
         */
        render(data) {
            Dialog_1.default.open(this, data.returnValues.template);
            const buttonSubmit = document.getElementById("addParticipants");
            buttonSubmit.disabled = true;
            UiItemListUser.init("participantsInput", {
                callbackChange: (elementId, values) => {
                    buttonSubmit.disabled = values.length === 0;
                },
                excludedSearchValues: data.returnValues.excludedSearchValues,
                maxItems: data.returnValues.maxItems,
                includeUserGroups: data.returnValues.canAddGroupParticipants && data.returnValues.restrictUserGroupIDs.length > 0,
                restrictUserGroupIDs: data.returnValues.restrictUserGroupIDs,
                csvPerType: true,
            });
            buttonSubmit.addEventListener("click", () => this.submit());
        }
        /**
         * Sends a request to add participants.
         */
        submit() {
            const participants = [];
            const participantsGroupIDs = [];
            UiItemListUser.getValues("participantsInput").forEach((value) => {
                if (value.type === "group") {
                    participantsGroupIDs.push(value.objectId);
                }
                else {
                    participants.push(value.value);
                }
            });
            const parameters = {
                participants: participants,
                participantsGroupIDs: participantsGroupIDs,
                visibility: null,
            };
            const visibility = Dialog_1.default.getDialog(this).content.querySelector('input[name="messageVisibility"]:checked, input[name="messageVisibility"][type="hidden"]');
            if (visibility) {
                parameters.visibility = visibility.value;
            }
            Ajax.api(this, {
                actionName: "addParticipants",
                parameters: parameters,
            });
        }
        _dialogSetup() {
            return {
                id: "conversationAddParticipants",
                options: {
                    title: Language.get("wcf.conversation.edit.addParticipants"),
                },
                source: null,
            };
        }
    }
    return UiParticipantAdd;
});
