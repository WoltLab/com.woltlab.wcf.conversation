/**
 * Inline editor for conversation messages.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since  6.2
 */

import UiMessageInlineEditor from "WoltLabSuite/Core/Ui/Message/InlineEditor";

export class UiConversationMessageInlineEditor extends UiMessageInlineEditor {
  constructor(containerID: number) {
    super({
      className: "wcf\\data\\conversation\\message\\ConversationMessageAction",
      containerId: containerID.toString(),
    });
  }
}
