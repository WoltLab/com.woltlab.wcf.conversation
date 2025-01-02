/**
 * Executes global conversation-related JavaScript code.
 *
 * @author  Marcel Werk
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
define(["require", "exports", "WoltLabSuite/Core/LazyLoader", "../Api/Conversations/GetConversationPopover"], function (require, exports, LazyLoader_1, GetConversationPopover_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = void 0;
    function setupPopover() {
        (0, LazyLoader_1.whenFirstSeen)(".conversationLink", () => {
            void new Promise((resolve_1, reject_1) => { require(["WoltLabSuite/Core/Component/Popover"], resolve_1, reject_1); }).then(__importStar).then(({ setupFor }) => {
                setupFor({
                    endpoint: async (objectId) => {
                        return (await (0, GetConversationPopover_1.getConversationPopover)(objectId)).unwrap();
                    },
                    identifier: "com.woltlab.wcf.conversation",
                    selector: ".conversationLink",
                });
            });
        });
    }
    function setup() {
        setupPopover();
    }
    exports.setup = setup;
});
