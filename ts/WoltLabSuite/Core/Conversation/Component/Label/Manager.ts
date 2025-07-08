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
import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";

interface LabelFormResponse {
  deleteLabel: boolean;
  labelID: number;
  label: string;
  cssClassName: string;
}

export class LabelManager {
  #formLink: string;
  #dialog?: WoltlabCoreDialogElement;
  #maxLabels = 0;
  #labelCount = 0;
  #listViewId: string;

  constructor(listViewId: string, formLink: string) {
    this.#formLink = formLink;
    this.#listViewId = listViewId;

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
        const button = document.querySelector<HTMLButtonElement>(
          `#${this.#listViewId}_filters .button[data-filter="label"][data-filter-value="${response.result.labelID}"]`,
        );

        if (button) {
          button.dispatchEvent(new Event("click", { bubbles: true, cancelable: true }));
        } else {
          this.#reloadListView();
        }
      } else if (labelId) {
        this.#reloadListView();
      }

      this.#dialog?.close();
      showDefaultSuccessSnackbar();
    }
  }

  #reloadListView(): void {
    const listView = document.getElementById(`${this.#listViewId}_items`)!;
    listView.dispatchEvent(new CustomEvent("interaction:invalidate-all"));
  }

  #updateAddButtonState(): void {
    this.#dialog!.content.querySelector<HTMLButtonElement>(".addLabel")!.disabled = this.#labelCount >= this.#maxLabels;
  }
}
