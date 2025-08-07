/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Component/Snackbar", "WoltLabSuite/Core/Helper/PromiseMutex", "../../Api/Conversations/MarkAllConversationsAsRead"], function (require, exports, Snackbar_1, PromiseMutex_1, MarkAllConversationsAsRead_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    async function markAllAsRead(listView) {
        (await (0, MarkAllConversationsAsRead_1.markAllConversationsAsRead)()).unwrap();
        listView.dispatchEvent(new CustomEvent("interaction:invalidate-all"));
        document.querySelector("#unreadConversations .badgeUpdate")?.remove();
        (0, Snackbar_1.showDefaultSuccessSnackbar)();
    }
    function setup(listView) {
        document.querySelectorAll(".markAllAsReadButton").forEach((element) => {
            element.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                await markAllAsRead(listView);
            }));
        });
    }
});
