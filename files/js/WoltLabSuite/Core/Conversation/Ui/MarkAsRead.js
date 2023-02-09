/**
 * Handles the mark as read button for single conversations.
 *
 * @author  Marcel Werk
 * @copyright  2001-2022 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax"], function (require, exports, Ajax_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = void 0;
    const unreadConversations = new WeakSet();
    async function markAsRead(conversation) {
        const conversationId = parseInt(conversation.dataset.conversationId, 10);
        await (0, Ajax_1.dboAction)("markAsRead", "wcf\\data\\conversation\\ConversationAction").objectIds([conversationId]).dispatch();
        conversation.classList.remove("new");
        conversation.querySelector(".columnAvatar p")?.removeAttribute("title");
    }
    function setup() {
        document.querySelectorAll(".conversationList .new .columnAvatar").forEach((el) => {
            if (!unreadConversations.has(el)) {
                unreadConversations.add(el);
                el.addEventListener("dblclick", (event) => {
                    event.preventDefault();
                    const conversation = el.closest(".conversation");
                    if (!conversation.classList.contains("new")) {
                        return;
                    }
                    void markAsRead(conversation);
                }, { once: true });
            }
        });
    }
    exports.setup = setup;
});
