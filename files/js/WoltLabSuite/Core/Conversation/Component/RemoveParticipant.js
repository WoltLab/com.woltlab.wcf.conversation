/**
 * Reacts to participants being removed from a conversation.
 *
 * @author Olaf Braun, Matthias Schmidt
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
define(["require", "exports", "WoltLabSuite/Core/Helper/Selector", "../../Api/Conversations/RemoveParticipant", "WoltLabSuite/Core/Helper/PromiseMutex", "WoltLabSuite/Core/Component/Confirmation", "WoltLabSuite/Core/Language"], function (require, exports, Selector_1, RemoveParticipant_1, PromiseMutex_1, Confirmation_1, Language_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    function setup() {
        (0, Selector_1.wheneverSeen)(".conversationRemoveParticipant", (element) => {
            const participantId = parseInt(element.dataset.participantId || "0", 10);
            const conversationId = parseInt(element.dataset.conversationId || "0", 10);
            const confirmMessage = element.dataset.confirmMessage;
            element.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                const confirmed = await (0, Confirmation_1.confirmationFactory)()
                    .custom((0, Language_1.getPhrase)("wcf.global.confirmation.title"))
                    .message(confirmMessage);
                if (confirmed) {
                    const response = await (0, RemoveParticipant_1.removeParticipant)(conversationId, participantId);
                    if (!response.ok) {
                        return;
                    }
                    document.querySelector(".conversationParticipantList").outerHTML = response.value;
                }
            }));
        });
    }
});
