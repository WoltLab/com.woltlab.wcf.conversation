/**
 * Inline editor for conversation messages.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since  6.2
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Message/InlineEditor"], function (require, exports, tslib_1, InlineEditor_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.UiConversationMessageInlineEditor = void 0;
    InlineEditor_1 = tslib_1.__importDefault(InlineEditor_1);
    class UiConversationMessageInlineEditor extends InlineEditor_1.default {
        constructor(containerID) {
            super({
                className: "wcf\\data\\conversation\\message\\ConversationMessageAction",
                containerId: containerID.toString(),
            });
        }
    }
    exports.UiConversationMessageInlineEditor = UiConversationMessageInlineEditor;
});
