/**
 * Clipboard for conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { add as addEvent } from "WoltLabSuite/Core/Event/Handler";
import { AjaxResponse, ClipboardActionData } from "WoltLabSuite/Core/Controller/Clipboard/Data";

// TODO complete types for EventData, add "conversationData"
interface EventData {
  data: ClipboardActionData;
  responseData: AjaxResponse;
}

// TODO add types for editorHandler

export function setup(editorHandler) {
  addEvent("com.woltlab.wcf.clipboard", "com.woltlab.wcf.conversation.conversation", (data: EventData) => {
    if (data.responseData === null) {
      execute(editorHandler, data.data.actionName, data.data.parameters);
    } else {
      evaluateResponse(editorHandler, data.data.actionName, data.responseData);
    }
  });
}

function execute(editorHandler, actionName: string, parameters) {
  if (actionName === "com.woltlab.wcf.conversation.conversation.assignLabel") {
    // TODO update to new typescript label editor
    new window.WCF.Conversation.Label.Editor(editorHandler, null, parameters.objectIDs);
  }
}

function evaluateResponse(editorHandler, actionName: string, data) {
  switch (actionName) {
    case "com.woltlab.wcf.conversation.conversation.leave":
    case "com.woltlab.wcf.conversation.conversation.leavePermanently":
    case "com.woltlab.wcf.conversation.conversation.markAsRead":
    case "com.woltlab.wcf.conversation.conversation.restore":
      window.location.reload();
      break;

    case "com.woltlab.wcf.conversation.conversation.close":
    case "com.woltlab.wcf.conversation.conversation.open":
      for (const conversationId in data.returnValues.conversationData) {
        if (Object.hasOwn(data.returnValues.conversationData, conversationId)) {
          const $data = data.returnValues.conversationData[conversationId];

          editorHandler.update(conversationId, $data.isClosed ? "close" : "open", $data);
        }
      }
      break;
  }
}
