/**
 * Executes global conversation-related JavaScript code.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { whenFirstSeen } from "WoltLabSuite/Core/LazyLoader";
import { getConversationPopover } from "../Api/Conversations/GetConversationPopover";

function setupPopover(): void {
  whenFirstSeen(".conversationLink", () => {
    void import("WoltLabSuite/Core/Component/Popover").then(({ setupFor }) => {
      setupFor({
        endpoint: async (objectId: number) => {
          return (await getConversationPopover(objectId)).unwrap();
        },
        identifier: "com.woltlab.wcf.conversation",
        selector: ".conversationLink",
      });
    });
  });

  whenFirstSeen(".conversationRemoveParticipant", () => {
    void import("../Component/Conversation/RemoveParticipant").then(({ setup }) => {
      setup();
    });
  });
}

export function setup(): void {
  setupPopover();
}
