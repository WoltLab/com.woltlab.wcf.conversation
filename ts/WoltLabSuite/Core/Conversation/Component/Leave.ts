/**
 * Handles the leave conversation action.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { getConversationLeaveDialog } from "../../Api/Conversations/GetConversationLeaveDialog";
import { dialogFactory } from "WoltLabSuite/Core/Component/Dialog";
import { getPhrase } from "WoltLabSuite/Core/Language";
import { leaveConversation, HideConversation } from "../../Api/Conversations/LeaveConversation";
import DomUtil from "WoltLabSuite/Core/Dom/Util";

type Environment = "conversation" | "";

export async function openDialog(conversationID: number, environment: Environment) {
  const result = await getConversationLeaveDialog(conversationID);
  if (result.ok) {
    const dialog = dialogFactory().fromHtml(result.value).asConfirmation();
    dialog.addEventListener("validate", (event) => {
      const checked = getSelectedValue(dialog) !== undefined;

      event.detail.push(Promise.resolve(checked));

      DomUtil.innerError(dialog.querySelector("dl")!, checked ? undefined : getPhrase("wcf.global.form.error.empty"));
    });
    dialog.addEventListener("primary", () => {
      const hideConversation = getSelectedValue(dialog)!;

      void leaveConversation(conversationID, hideConversation).then((leaveResult) => {
        if (!leaveResult.ok) {
          return;
        }

        if (environment === "conversation") {
          window.location.href = leaveResult.value;
        } else {
          window.location.reload();
        }
      });
    });
    dialog.show(getPhrase("wcf.conversation.leave.title"));
  }
}

function getSelectedValue(dialog: HTMLElement): HideConversation | undefined {
  const selected = dialog.querySelector<HTMLInputElement>('input[name="hideConversation"]:checked');

  return selected ? (parseInt(selected.value) as HideConversation) : undefined;
}
