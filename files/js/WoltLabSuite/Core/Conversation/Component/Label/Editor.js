/**
 * Editor to assign labels to conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Component/Dialog", "../../../Api/Conversations/GetConversationLabels", "WoltLabSuite/Core/Form/Builder/Manager", "../../../Api/Conversations/AssignConversationLabels", "WoltLabSuite/Core/Controller/Clipboard", "WoltLabSuite/Core/Ui/Notification", "../EditorHandler"], function (require, exports, tslib_1, Dialog_1, GetConversationLabels_1, FormBuilderManager, AssignConversationLabels_1, Clipboard_1, Notification_1, EditorHandler_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.openDialog = openDialog;
    FormBuilderManager = tslib_1.__importStar(FormBuilderManager);
    async function openDialog(conversationIDs) {
        const response = await (0, GetConversationLabels_1.getConversationLabels)(conversationIDs);
        if (!response.ok) {
            throw new Error("Failed to load form to assign labels to conversations.");
        }
        const dialog = (0, Dialog_1.dialogFactory)().fromHtml(response.value.template).asPrompt();
        dialog.addEventListener("afterClose", () => {
            if (FormBuilderManager.hasForm(response.value.formId)) {
                FormBuilderManager.unregisterForm(response.value.formId);
            }
        });
        dialog.addEventListener("primary", () => {
            void FormBuilderManager.getData(response.value.formId).then(async (data) => {
                const labelIDs = [];
                for (const labelID of data["conversationLabel_labelIDs"]) {
                    labelIDs.push(parseInt(labelID));
                }
                await (0, AssignConversationLabels_1.assignConversationLabels)(conversationIDs, labelIDs);
                assignLabels(conversationIDs, labelIDs);
                (0, Clipboard_1.reload)();
            });
        });
        dialog.show(response.value.title);
    }
    function assignLabels(conversationIDs, labelIDs) {
        conversationIDs.forEach((conversationID) => {
            (0, EditorHandler_1.getConversationEditor)(conversationID).labelIDs = labelIDs;
        });
        (0, Notification_1.show)();
    }
});
