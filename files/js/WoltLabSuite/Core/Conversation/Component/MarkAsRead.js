/**
 * Handles the mark as read button for single conversations.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Component/Snackbar", "WoltLabSuite/Core/Helper/PromiseMutex", "WoltLabSuite/Core/Helper/Selector"], function (require, exports, Ajax_1, Snackbar_1, PromiseMutex_1, Selector_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    async function markAsRead(button) {
        const objectId = parseInt(button.dataset.objectId, 10);
        await (0, Ajax_1.dboAction)("markAsRead", "wcf\\data\\conversation\\ConversationAction").objectIds([objectId]).dispatch();
        button.remove();
        (0, Snackbar_1.showDefaultSuccessSnackbar)();
    }
    function setup() {
        (0, Selector_1.wheneverFirstSeen)(".conversationList__item__markAsRead", (element) => {
            element.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                await markAsRead(element);
            }));
        });
    }
});
