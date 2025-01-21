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

interface ConversationData {
  isClosed: boolean;
}

interface ResponseData {
  returnValues: {
    conversationData: {
      [key: string]: ConversationData;
    };
  };
}

interface EventData {
  data: ClipboardActionData;
  responseData: null | (ResponseData & AjaxResponse);
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

function evaluateResponse(editorHandler, actionName: string, data: ResponseData) {
  switch (actionName) {
    case "com.woltlab.wcf.conversation.conversation.leave":
    case "com.woltlab.wcf.conversation.conversation.leavePermanently":
    case "com.woltlab.wcf.conversation.conversation.markAsRead":
    case "com.woltlab.wcf.conversation.conversation.restore":
      window.location.reload();
      break;

    case "com.woltlab.wcf.conversation.conversation.close":
    case "com.woltlab.wcf.conversation.conversation.open":
      Object.entries(data.returnValues.conversationData).forEach(([conversationId, conversationData]) => {
        editorHandler.update(conversationId, conversationData.isClosed ? "close" : "open", conversationData);
      });
      break;
  }
}
