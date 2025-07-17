/**
 * Handles the mark as read button for single conversations.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";
import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";
import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";
import { wheneverFirstSeen } from "WoltLabSuite/Core/Helper/Selector";

async function markAsRead(button: HTMLElement): Promise<void> {
  const objectId = parseInt(button.dataset.objectId!, 10);

  await dboAction("markAsRead", "wcf\\data\\conversation\\ConversationAction").objectIds([objectId]).dispatch();

  button.remove();

  showDefaultSuccessSnackbar();
}

export function setup(): void {
  wheneverFirstSeen(".conversationList__item__markAsRead", (element) => {
    element.addEventListener(
      "click",
      promiseMutex(async () => {
        await markAsRead(element);
      }),
    );
  });
}
