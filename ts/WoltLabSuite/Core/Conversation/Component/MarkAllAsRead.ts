/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";
import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";
import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";

async function markAllAsRead(): Promise<void> {
  await dboAction("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();

  document.querySelectorAll(".conversationList__item__markAsRead").forEach((element: HTMLElement) => {
    element.remove();
  });
  document.querySelector("#unreadConversations .badgeUpdate")?.remove();

  showDefaultSuccessSnackbar();
}

export function setup(): void {
  document.querySelectorAll(".markAllAsReadButton").forEach((element: HTMLElement) => {
    element.addEventListener(
      "click",
      promiseMutex(async () => {
        await markAllAsRead();
      }),
    );
  });
}
