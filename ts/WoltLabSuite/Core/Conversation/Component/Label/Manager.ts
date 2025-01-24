/**
 * Managed the labels of a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import {
  getConversationLabelManager,
  Response as ManagerResponse,
} from "../../../Api/Conversations/GetConversationLabelManager";
import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";
import { dialogFactory } from "WoltLabSuite/Core/Component/Dialog";
import WoltlabCoreDialogElement from "WoltLabSuite/Core/Element/woltlab-core-dialog";
import { getPhrase } from "WoltLabSuite/Core/Language";
import { show as showNotification } from "WoltLabSuite/Core/Ui/Notification";
import UiDropdownSimple from "WoltLabSuite/Core/Ui/Dropdown/Simple";
import { addAvailableLabel } from "../EditorHandler";

interface LabelFormResponse {
  deleteLabel: boolean;
  labelID: number;
  label: string;
  cssClassName: string;
}

export class LabelManager {
  #conversationListLink: string;
  #formLink: string;
  #dialog?: WoltlabCoreDialogElement;
  #maxLabels = 0;
  #labelCount = 0;

  constructor(formLink: string, conversationListLink: string) {
    this.#formLink = formLink;
    this.#conversationListLink = conversationListLink;

    document.getElementById("manageLabel")?.addEventListener(
      "click",
      promiseMutex(async () => {
        const response = await getConversationLabelManager();
        if (!response.ok) {
          throw new Error("Could not fetch conversation label manager");
        }

        this.#openDialog(response.value);
      }),
    );
  }

  #openDialog(data: ManagerResponse): void {
    this.#maxLabels = data.maxLabels;
    this.#labelCount = data.labelCount;

    this.#dialog = dialogFactory().fromHtml(data.template).withoutControls();
    this.#updateAddButtonState();
    this.#dialog.show(getPhrase("wcf.conversation.label.management"));

    this.#dialog?.content.querySelectorAll(".conversationLabelList .badge").forEach((badge: HTMLElement) => {
      const labelId = parseInt(badge.dataset.labelId!);
      badge.addEventListener(
        "click",
        promiseMutex(async () => {
          await this.#openForm(labelId);
        }),
      );
    });

    this.#dialog?.content.querySelector(".addLabel")?.addEventListener(
      "click",
      promiseMutex(async () => {
        await this.#openForm();
      }),
    );
  }

  async #openForm(labelId?: number) {
    const url = new URL(this.#formLink);
    if (labelId) {
      url.searchParams.set("labelID", labelId.toString());
    }

    const response = await dialogFactory().usingFormBuilder().fromEndpoint<LabelFormResponse>(url.toString());
    if (response.ok) {
      if (response.result.deleteLabel) {
        // check if delete label id is present within URL (causing an IllegalLinkException if reloading)
        const regex = new RegExp("(\\?|&)labelID=" + response.result.labelID);
        window.location.href = window.location.toString().replace(regex, "");
        return;
      }

      if (labelId) {
        window.location.reload();
        return;
      }

      this.#labelCount++;

      const button = document.createElement("button");
      button.type = "button";
      button.classList.add("badge", "label", response.result.cssClassName || "");
      button.dataset.labelId = response.result.labelID.toString();
      button.dataset.cssClassName = response.result.cssClassName;
      button.textContent = response.result.label;
      button.addEventListener(
        "click",
        promiseMutex(() => this.#openForm(response.result.labelID)),
      );

      const li = document.createElement("li");
      li.append(button);

      this.#dialog?.content.querySelector(".conversationLabelList")?.append(li);
      this.#insertLabel(response.result);

      this.#updateAddButtonState();
      showNotification();
    }
  }

  #updateAddButtonState(): void {
    this.#dialog!.content.querySelector<HTMLButtonElement>(".addLabel")!.disabled = this.#labelCount >= this.#maxLabels;
  }

  #insertLabel(data: LabelFormResponse): void {
    const listItem = document.createElement("li");
    const anchor = document.createElement("a");

    const url = new URL(this.#conversationListLink);
    url.searchParams.set("labelID", data.labelID.toString());
    anchor.href = url.toString();

    const span = document.createElement("span");
    span.className = `badge label${data.cssClassName ? " " + data.cssClassName : ""}`;
    span.textContent = data.label;
    span.dataset.labelID = data.labelID.toString();
    span.dataset.cssClassName = data.cssClassName;

    anchor.appendChild(span);
    listItem.appendChild(anchor);

    UiDropdownSimple.getDropdownMenu("conversationLabelFilter")
      ?.querySelector(".scrollableDropdownMenu")
      ?.append(listItem);

    addAvailableLabel({
      labelID: data.labelID,
      label: data.label,
      cssClassName: data.cssClassName,
      url: url.toString(),
    });
  }
}
