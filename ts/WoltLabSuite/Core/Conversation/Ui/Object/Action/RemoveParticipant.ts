/**
 * Reacts to participants being removed from a conversation.
 *
 * @author  Matthias Schmidt
 * @copyright  2001-2021 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

import UiObjectActionHandler from "WoltLabSuite/Core/Ui/Object/Action/Handler";
import { ObjectActionData } from "WoltLabSuite/Core/Ui/Object/Data";

function removeParticipant(data: ObjectActionData): void {
  data.objectElement.querySelector(".userLink")!.classList.add("conversationLeft");
  data.objectElement.querySelector(".jsObjectAction[data-object-action='removeParticipant']")!.remove();
}

export function setup(): void {
  new UiObjectActionHandler("removeParticipant", [], removeParticipant);
}
