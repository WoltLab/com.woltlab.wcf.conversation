/**
 * Quote manager for conversation messages.
 *
 * @author  Matthias Schmidt
 * @copyright 2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @woltlabExcludeBundle tiny
 */
define(["require", "exports", "WoltLabSuite/Core/Ui/Message/Quote"], function (require, exports, Quote_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.UiConversationMessageQuote = void 0;
    class UiConversationMessageQuote extends Quote_1.UiMessageQuote {
        constructor(quoteManager) {
            super(quoteManager, "wcf\\data\\conversation\\message\\ConversationMessageAction", "com.woltlab.wcf.conversation.message", ".message", ".messageBody", ".messageBody > div > div.messageText", true);
        }
    }
    exports.UiConversationMessageQuote = UiConversationMessageQuote;
    exports.default = UiConversationMessageQuote;
});
