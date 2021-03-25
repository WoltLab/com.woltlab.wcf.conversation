/**
 * Reacts to participants being removed from a conversation.
 *
 * @author  Matthias Schmidt
 * @copyright  2001-2021 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Core/Conversation/Ui/Object/Action/RemoveParticipant
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Object/Action/Handler"], function (require, exports, tslib_1, Handler_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = void 0;
    Handler_1 = tslib_1.__importDefault(Handler_1);
    function removeParticipant(data, objectElement) {
        objectElement.querySelector(".userLink").classList.add("conversationLeft");
        objectElement.querySelector(".jsObjectAction[data-object-action='removeParticipant']").remove();
    }
    function setup() {
        new Handler_1.default("removeParticipant", [], removeParticipant);
    }
    exports.setup = setup;
});
