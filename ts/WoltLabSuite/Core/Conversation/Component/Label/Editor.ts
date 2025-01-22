/**
 * Editor to assign labels to conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { dialogFactory } from "WoltLabSuite/Core/Component/Dialog";
import { getConversationLabels } from "../../../Api/Conversations/GetConversationLabels";
import * as FormBuilderManager from "WoltLabSuite/Core/Form/Builder/Manager";
import { assignConversationLabels } from "../../../Api/Conversations/AssignConversationLabels";
import { reload as reloadClipboard } from "WoltLabSuite/Core/Controller/Clipboard";
import { show as showNotification } from "WoltLabSuite/Core/Ui/Notification";

// TODO type for editorHandler

export async function openDialog(editorHandler, conversationIDs: number[]) {
  const response = await getConversationLabels(conversationIDs);
  if (!response.ok) {
    throw new Error("Failed to load form to assign labels to conversations.");
  }

  const dialog = dialogFactory().fromHtml(response.value.template).asPrompt();
  dialog.addEventListener("afterClose", () => {
    if (FormBuilderManager.hasForm(response.value.formId)) {
      FormBuilderManager.unregisterForm(response.value.formId);
    }
  });
  dialog.addEventListener("primary", () => {
    void FormBuilderManager.getData(response.value.formId).then(async (data) => {
      const labelIDs: number[] = [];
      for (const labelID of data["conversationLabel_labelIDs"]) {
        labelIDs.push(parseInt(labelID));
      }

      await assignConversationLabels(conversationIDs, labelIDs);

      assignLabels(editorHandler, conversationIDs, labelIDs);
      reloadClipboard();
    });
  });

  dialog.show(response.value.title);
}

function assignLabels(editorHandler, conversationIDs: number[], labelIDs: number[]) {
  conversationIDs.forEach((conversationID) => {
    editorHandler.update(conversationID, "labelIDs", labelIDs);
  });

  showNotification();
}
