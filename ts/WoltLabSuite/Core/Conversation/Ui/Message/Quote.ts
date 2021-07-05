/**
 * Quote manager for conversation messages.
 *
 * @author  Matthias Schmidt
 * @copyright 2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Core/Conversation/Ui/Message/Quote
 * @woltlabExcludeBundle tiny
 */

import { UiMessageQuote, WCFMessageQuoteManager } from "WoltLabSuite/Core/Ui/Message/Quote";

export class UiConversationMessageQuote extends UiMessageQuote {
  constructor(quoteManager: WCFMessageQuoteManager) {
    super(
      quoteManager,
      "wcf\\data\\conversation\\message\\ConversationMessageAction",
      "com.woltlab.wcf.conversation.message",
      ".message",
      ".messageBody",
      ".messageBody > div > div.messageText",
      true,
    );
  }
}

export default UiConversationMessageQuote;
