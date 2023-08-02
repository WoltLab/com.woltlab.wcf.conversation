/**
 * Handles the mark as read button for single conversations.
 *
 * @author  Marcel Werk
 * @copyright  2001-2022 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";

const unreadConversations = new WeakSet();

async function markAsRead(conversation: HTMLElement): Promise<void> {
  const conversationId = parseInt(conversation.dataset.conversationId!, 10);

  await dboAction("markAsRead", "wcf\\data\\conversation\\ConversationAction").objectIds([conversationId]).dispatch();

  conversation.classList.remove("new");
  conversation.querySelector(".columnAvatar p")?.removeAttribute("title");
}

export function setup(): void {
  document.querySelectorAll(".conversationList .new .columnAvatar").forEach((el: HTMLElement) => {
    if (!unreadConversations.has(el)) {
      unreadConversations.add(el);

      el.addEventListener(
        "dblclick",
        (event) => {
          event.preventDefault();

          const conversation = el.closest<HTMLElement>(".conversation")!;
          if (!conversation.classList.contains("new")) {
            return;
          }
          void markAsRead(conversation);
        },
        { once: true },
      );
    }
  });
}
