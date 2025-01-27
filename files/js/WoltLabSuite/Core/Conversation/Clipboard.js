/**
 * Clipboard for conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Event/Handler", "./Component/Label/Editor", "./Component/EditorHandler"], function (require, exports, Handler_1, Editor_1, EditorHandler_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    function setup() {
        (0, Handler_1.add)("com.woltlab.wcf.clipboard", "com.woltlab.wcf.conversation.conversation", (data) => {
            if (data.responseData === null) {
                execute(data.data.actionName, data.data.parameters);
            }
            else {
                evaluateResponse(data.data.actionName, data.responseData);
            }
        });
    }
    function execute(actionName, parameters) {
        if (actionName === "com.woltlab.wcf.conversation.conversation.assignLabel") {
            void (0, Editor_1.openDialog)(parameters.objectIDs);
        }
    }
    function evaluateResponse(actionName, data) {
        switch (actionName) {
            case "com.woltlab.wcf.conversation.conversation.leave":
            case "com.woltlab.wcf.conversation.conversation.leavePermanently":
            case "com.woltlab.wcf.conversation.conversation.markAsRead":
            case "com.woltlab.wcf.conversation.conversation.restore":
                window.location.reload();
                break;
            case "com.woltlab.wcf.conversation.conversation.close":
            case "com.woltlab.wcf.conversation.conversation.open":
                Object.entries(data.returnValues.conversationData).forEach(([conversationId, conversationData]) => {
                    (0, EditorHandler_1.getConversationEditor)(parseInt(conversationId)).isClosed = conversationData.isClosed;
                });
                break;
        }
    }
});
