/**
 * Reacts to participants being removed from a conversation.
 *
 * @author Olaf Braun, Matthias Schmidt
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

import { wheneverFirstSeen } from "WoltLabSuite/Core/Helper/Selector";
import { removeParticipant } from "../../Api/Conversations/RemoveParticipant";
import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";
import { confirmationFactory } from "WoltLabSuite/Core/Component/Confirmation";
import { getPhrase } from "WoltLabSuite/Core/Language";

export function setup(): void {
  wheneverFirstSeen(".conversationRemoveParticipant", (element: HTMLButtonElement) => {
    const participantId = parseInt(element.dataset.participantId || "0", 10);
    const conversationId = parseInt(element.dataset.conversationId || "0", 10);
    const confirmMessage = element.dataset.confirmMessage!;

    element.addEventListener(
      "click",
      promiseMutex(async () => {
        const confirmed = await confirmationFactory()
          .custom(getPhrase("wcf.global.confirmation.title"))
          .message(confirmMessage);

        if (confirmed) {
          const response = await removeParticipant(conversationId, participantId);
          if (!response.ok) {
            return;
          }

          document.querySelector(".conversationParticipantList")!.outerHTML = response.value;
        }
      }),
    );
  });
}
