/**
 * Marks all conversations as read.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";
import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";
import { markAllConversationsAsRead } from "../../Api/Conversations/MarkAllConversationsAsRead";

async function markAllAsRead(listView: HTMLElement): Promise<void> {
  (await markAllConversationsAsRead()).unwrap();

  listView.dispatchEvent(new CustomEvent("interaction:invalidate-all"));

  document.querySelector("#unreadConversations .badgeUpdate")?.remove();

  showDefaultSuccessSnackbar();
}

export function setup(listView: HTMLElement): void {
  document.querySelectorAll(".markAllAsReadButton").forEach((element: HTMLElement) => {
    element.addEventListener(
      "click",
      promiseMutex(async () => {
        await markAllAsRead(listView);
      }),
    );
  });
}
