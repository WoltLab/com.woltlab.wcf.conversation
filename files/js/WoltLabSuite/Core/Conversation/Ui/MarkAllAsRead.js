/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2022 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Conversation/Ui/MarkAllAsRead
 * @since 6.0
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Ui/Notification"], function (require, exports, tslib_1, Ajax_1, UiNotification) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = void 0;
    UiNotification = tslib_1.__importStar(UiNotification);
    async function markAllAsRead() {
        var _a;
        await (0, Ajax_1.dboAction)("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();
        document.querySelectorAll(".conversationList .new").forEach((el) => {
            el.classList.remove("new");
        });
        (_a = document.querySelector("#unreadConversations .badgeUpdate")) === null || _a === void 0 ? void 0 : _a.remove();
        UiNotification.show();
    }
    function setup() {
        document.querySelectorAll(".markAllAsReadButton").forEach((el) => {
            el.addEventListener("click", (event) => {
                event.preventDefault();
                void markAllAsRead();
            });
        });
    }
    exports.setup = setup;
});
