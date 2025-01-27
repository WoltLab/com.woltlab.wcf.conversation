/**
 * Handles editing for conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Helper/Selector", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/StringUtil", "WoltLabSuite/Core/Controller/Clipboard"], function (require, exports, Selector_1, Language_1, StringUtil_1, Clipboard_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.getAvailableLabels = getAvailableLabels;
    exports.addAvailableLabel = addAvailableLabel;
    exports.getLabel = getLabel;
    exports.getConversationEditor = getConversationEditor;
    exports.setup = setup;
    const _availableLabels = new Map();
    const conversations = new Map();
    class ConversationEditor {
        #conversation;
        #isClosed;
        #canAddParticipants;
        #canCloseConversation;
        #labelIDs;
        #isConversationListItem;
        constructor(conversation) {
            this.#conversation = conversation;
            this.#isConversationListItem = conversation.classList.contains("jsClipboardObject");
            this.#isClosed = conversation.dataset.isClosed === "1";
            this.#canAddParticipants = conversation.dataset.canAddParticipants === "1";
            this.#canCloseConversation = conversation.dataset.canCloseConversation === "1";
            this.#labelIDs = JSON.parse(conversation.dataset.labelIds);
        }
        get isClosed() {
            return this.#isClosed;
        }
        set isClosed(isClosed) {
            this.#isClosed = isClosed;
            this.#conversation.dataset.isClosed = isClosed ? "1" : "0";
            if (isClosed) {
                this.#getContainer(".statusIcons")?.insertAdjacentHTML("beforeend", `<li>
  <span class="jsTooltip jsIconLock" title="${(0, Language_1.getPhrase)("wcf.global.state.closed")}">
    <fa-icon size="16" name="lock"></fa-icon>
  </span>
</li>`);
            }
            else {
                this.#getContainer(".statusIcons").querySelector("li .jsIconLock")?.parentElement?.remove();
            }
            (0, Clipboard_1.reload)();
        }
        get canAddParticipants() {
            return this.#canAddParticipants;
        }
        get canCloseConversation() {
            return this.#canCloseConversation;
        }
        get labelIDs() {
            return this.#labelIDs;
        }
        set labelIDs(labelIDs) {
            this.#labelIDs = labelIDs;
            this.#conversation.dataset.labelIDs = JSON.stringify(labelIDs);
            const labels = labelIDs.map((labelID) => {
                return getLabel(labelID);
            });
            let labelList = this.#getContainer(".columnSubject > .labelList");
            if (labelIDs.length == 0) {
                labelList?.remove();
            }
            else {
                if (!labelList) {
                    labelList = document.createElement("ul");
                    labelList.classList.add("labelList");
                    this.#getContainer(".columnSubject")?.insertAdjacentElement("afterbegin", labelList);
                }
                // remove old labels
                labelList.innerHTML = "";
                for (const label of labels) {
                    if (this.#isConversationListItem) {
                        labelList.insertAdjacentHTML("beforeend", `<li><a href="${(0, StringUtil_1.escapeHTML)(label.url)}" class="badge label ${label.cssClassName}">${(0, StringUtil_1.escapeHTML)(label.label)}</a></li>`);
                    }
                    else {
                        labelList.insertAdjacentHTML("beforeend", `<li><span class="badge label ${label.cssClassName}">${(0, StringUtil_1.escapeHTML)(label.label)}</span></li>`);
                    }
                }
            }
            (0, Clipboard_1.reload)();
        }
        #getContainer(selector) {
            if (this.#isConversationListItem) {
                return this.#conversation.querySelector(selector);
            }
            else {
                return document.querySelector(".contentHeaderTitle > .contentHeaderMetaData");
            }
        }
    }
    function getAvailableLabels() {
        return Array.from(_availableLabels.values());
    }
    function addAvailableLabel(label) {
        _availableLabels.set(label.labelID, label);
    }
    function getLabel(labelID) {
        return _availableLabels.get(labelID);
    }
    function getConversationEditor(conversationID) {
        return conversations.get(conversationID);
    }
    function setup(availableLabels) {
        availableLabels.forEach((label) => {
            _availableLabels.set(label.labelID, label);
        });
        (0, Selector_1.wheneverFirstSeen)(".conversation", (conversation) => {
            conversations.set(parseInt(conversation.dataset.conversationId), new ConversationEditor(conversation));
        });
    }
});
