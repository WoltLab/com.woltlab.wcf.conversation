/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2022 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Conversation/Ui/MarkAllAsRead
 * @since 6.0
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";
import * as UiNotification from "WoltLabSuite/Core/Ui/Notification";

async function markAllAsRead(): Promise<void> {
  await dboAction("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();

  document.querySelectorAll(".conversationList .new").forEach((el: HTMLElement) => {
    el.classList.remove("new");
  });
  document.querySelector("#unreadConversations .badgeUpdate")?.remove();

  UiNotification.show();
}

export function setup(): void {
  document.querySelectorAll(".markAllAsReadButton").forEach((el: HTMLElement) => {
    el.addEventListener("click", (event) => {
      event.preventDefault();

      void markAllAsRead();
    });
  });
}
