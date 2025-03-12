/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2022 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";
import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";

async function markAllAsRead(): Promise<void> {
  await dboAction("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();

  document.querySelectorAll(".conversationList .new").forEach((el: HTMLElement) => {
    el.classList.remove("new");
  });
  document.querySelector("#unreadConversations .badgeUpdate")?.remove();

  showDefaultSuccessSnackbar();
}

export function setup(): void {
  document.querySelectorAll(".markAllAsReadButton").forEach((el: HTMLElement) => {
    el.addEventListener("click", (event) => {
      event.preventDefault();

      void markAllAsRead();
    });
  });
}
