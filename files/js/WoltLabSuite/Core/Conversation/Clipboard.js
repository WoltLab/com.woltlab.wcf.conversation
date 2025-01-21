/**
 * Clipboard for conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Event/Handler"], function (require, exports, Handler_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    // TODO add types for editorHandler
    function setup(editorHandler) {
        (0, Handler_1.add)("com.woltlab.wcf.clipboard", "com.woltlab.wcf.conversation.conversation", (data) => {
            if (data.responseData === null) {
                execute(editorHandler, data.data.actionName, data.data.parameters);
            }
            else {
                evaluateResponse(editorHandler, data.data.actionName, data.responseData);
            }
        });
    }
    function execute(editorHandler, actionName, parameters) {
        if (actionName === "com.woltlab.wcf.conversation.conversation.assignLabel") {
            // TODO update to new typescript label editor
            new window.WCF.Conversation.Label.Editor(editorHandler, null, parameters.objectIDs);
        }
    }
    function evaluateResponse(editorHandler, actionName, data) {
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
                    editorHandler.update(conversationId, conversationData.isClosed ? "close" : "open", conversationData);
                });
                break;
        }
    }
});
