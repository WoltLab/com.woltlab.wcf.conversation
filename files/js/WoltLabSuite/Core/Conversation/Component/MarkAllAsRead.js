/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Component/Snackbar", "WoltLabSuite/Core/Helper/PromiseMutex"], function (require, exports, Ajax_1, Snackbar_1, PromiseMutex_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    async function markAllAsRead() {
        await (0, Ajax_1.dboAction)("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();
        document.querySelectorAll(".conversationList__item__markAsRead").forEach((element) => {
            element.remove();
        });
        document.querySelector("#unreadConversations .badgeUpdate")?.remove();
        (0, Snackbar_1.showDefaultSuccessSnackbar)();
    }
    function setup() {
        document.querySelectorAll(".markAllAsReadButton").forEach((element) => {
            element.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                await markAllAsRead();
            }));
        });
    }
});
