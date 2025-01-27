/**
 * Handles the leave conversation action.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "tslib", "../../Api/Conversations/GetConversationLeaveDialog", "WoltLabSuite/Core/Component/Dialog", "WoltLabSuite/Core/Language", "../../Api/Conversations/LeaveConversation", "WoltLabSuite/Core/Dom/Util"], function (require, exports, tslib_1, GetConversationLeaveDialog_1, Dialog_1, Language_1, LeaveConversation_1, Util_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.openDialog = openDialog;
    Util_1 = tslib_1.__importDefault(Util_1);
    async function openDialog(conversationID, environment) {
        const result = await (0, GetConversationLeaveDialog_1.getConversationLeaveDialog)(conversationID);
        if (result.ok) {
            const dialog = (0, Dialog_1.dialogFactory)().fromHtml(result.value).asConfirmation();
            dialog.addEventListener("validate", (event) => {
                const checked = getSelectedValue(dialog) !== undefined;
                event.detail.push(Promise.resolve(checked));
                Util_1.default.innerError(dialog.querySelector("dl"), checked ? undefined : (0, Language_1.getPhrase)("wcf.global.form.error.empty"));
            });
            dialog.addEventListener("primary", () => {
                const hideConversation = getSelectedValue(dialog);
                void (0, LeaveConversation_1.leaveConversation)(conversationID, hideConversation).then((leaveResult) => {
                    if (!leaveResult.ok) {
                        return;
                    }
                    if (environment === "conversation") {
                        window.location.href = leaveResult.value;
                    }
                    else {
                        window.location.reload();
                    }
                });
            });
            dialog.show((0, Language_1.getPhrase)("wcf.conversation.leave.title"));
        }
    }
    function getSelectedValue(dialog) {
        const selected = dialog.querySelector('input[name="hideConversation"]:checked');
        return selected ? parseInt(selected.value) : undefined;
    }
});
