/**
 * Handles the quick reply for conversations.
 *
 * @author Tim Duesterhus
 * @copyright 2001-2023 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @woltlabExcludeBundle tiny
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Message/Reply"], function (require, exports, tslib_1, Reply_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Reply = void 0;
    Reply_1 = tslib_1.__importDefault(Reply_1);
    class Reply extends Reply_1.default {
        _insertMessage(...args) {
            this._content.querySelector(".invisibleParticipantWarning")?.remove();
            super._insertMessage(...args);
        }
    }
    exports.Reply = Reply;
    exports.default = Reply;
});
