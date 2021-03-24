/**
 * Reacts to participants being removed from a conversation.
 *
 * @author  Matthias Schmidt
 * @copyright  2001-2021 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Core/Conversation/Ui/Object/Action/RemoveParticipant
 */

import UiObjectActionHandler from "WoltLabSuite/Core/Ui/Object/Action/Handler";
import { DatabaseObjectActionResponse } from "WoltLabSuite/Core/Ajax/Data";

function removeParticipant(data: DatabaseObjectActionResponse, objectElement: HTMLElement): void {
  objectElement.querySelector(".userLink")!.classList.add("conversationLeft");
  objectElement.querySelector(".jsObjectAction[data-object-action='removeParticipant']")!.remove();
}

export function setup(): void {
  new UiObjectActionHandler("removeParticipant", [], removeParticipant);
}
