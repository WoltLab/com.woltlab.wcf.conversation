/**
 * Handles editing for conversations.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { wheneverFirstSeen } from "WoltLabSuite/Core/Helper/Selector";
import { getPhrase } from "WoltLabSuite/Core/Language";
import { escapeHTML } from "WoltLabSuite/Core/StringUtil";
import { reload as reloadClipboard } from "WoltLabSuite/Core/Controller/Clipboard";

export interface Label {
  cssClassName: string;
  labelID: number;
  label: string;
  url?: string;
}

const _availableLabels: Map<number, Label> = new Map();
const conversations = new Map<number, ConversationEditor>();

class ConversationEditor {
  #conversation: HTMLElement;
  #isClosed: boolean;
  #canAddParticipants: boolean;
  #canCloseConversation: boolean;
  #labelIDs: number[];
  #isConversationListItem: boolean;

  constructor(conversation: HTMLElement) {
    this.#conversation = conversation;
    this.#isConversationListItem = conversation.classList.contains("jsClipboardObject");
    this.#isClosed = conversation.dataset.isClosed === "1";
    this.#canAddParticipants = conversation.dataset.canAddParticipants === "1";
    this.#canCloseConversation = conversation.dataset.canCloseConversation === "1";
    this.#labelIDs = JSON.parse(conversation.dataset.labelIds!);
  }

  get isClosed(): boolean {
    return this.#isClosed;
  }

  set isClosed(isClosed: boolean) {
    this.#isClosed = isClosed;
    this.#conversation.dataset.isClosed = isClosed ? "1" : "0";

    if (isClosed) {
      this.#getContainer(".statusIcons")?.insertAdjacentHTML(
        "beforeend",
        `<li>
  <span class="jsTooltip jsIconLock" title="${getPhrase("wcf.global.state.closed")}">
    <fa-icon size="16" name="lock"></fa-icon>
  </span>
</li>`,
      );
    } else {
      this.#getContainer(".statusIcons").querySelector("li .jsIconLock")?.parentElement?.remove();
    }

    reloadClipboard();
  }

  get canAddParticipants(): boolean {
    return this.#canAddParticipants;
  }

  get canCloseConversation(): boolean {
    return this.#canCloseConversation;
  }

  get labelIDs(): number[] {
    return this.#labelIDs;
  }

  set labelIDs(labelIDs: number[]) {
    this.#labelIDs = labelIDs;
    this.#conversation.dataset.labelIDs = JSON.stringify(labelIDs);

    const labels = labelIDs.map((labelID) => {
      return getLabel(labelID)!;
    });

    let labelList = this.#getContainer(".columnSubject > .labelList");
    if (labelIDs.length == 0) {
      labelList?.remove();
    } else {
      if (!labelList) {
        labelList = document.createElement("ul");
        labelList.classList.add("labelList");
        this.#getContainer(".columnSubject")?.insertAdjacentElement("afterbegin", labelList);
      }

      // remove old labels
      labelList.innerHTML = "";

      for (const label of labels) {
        if (this.#isConversationListItem) {
          labelList.insertAdjacentHTML(
            "beforeend",
            `<li><a href="${escapeHTML(label.url!)}" class="badge label ${label.cssClassName}">${escapeHTML(label.label)}</a></li>`,
          );
        } else {
          labelList.insertAdjacentHTML(
            "beforeend",
            `<li><span class="badge label ${label.cssClassName}">${escapeHTML(label.label)}</span></li>`,
          );
        }
      }
    }

    reloadClipboard();
  }

  #getContainer(selector: string): HTMLElement {
    if (this.#isConversationListItem) {
      return this.#conversation.querySelector(selector)!;
    } else {
      return document.querySelector(".contentHeaderTitle > .contentHeaderMetaData")!;
    }
  }
}

export function getAvailableLabels(): Label[] {
  return Array.from(_availableLabels.values());
}

export function addAvailableLabel(label: Label) {
  _availableLabels.set(label.labelID, label);
}

export function getLabel(labelID: number): Label | undefined {
  return _availableLabels.get(labelID);
}

export function getConversationEditor(conversationID: number): ConversationEditor | undefined {
  return conversations.get(conversationID);
}

export function setup(availableLabels: Label[]) {
  availableLabels.forEach((label) => {
    _availableLabels.set(label.labelID, label);
  });

  wheneverFirstSeen(".conversation", (conversation) => {
    conversations.set(parseInt(conversation.dataset.conversationId!), new ConversationEditor(conversation));
  });
}
