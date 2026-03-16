/**
 * Managed the labels of a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { promiseMutex } from "WoltLabSuite/Core/Helper/PromiseMutex";
import { dialogFactory } from "WoltLabSuite/Core/Component/Dialog";
import { wheneverFirstSeen } from "WoltLabSuite/Core/Helper/Selector";
import { showDefaultSuccessSnackbar } from "WoltLabSuite/Core/Component/Snackbar";

interface LabelFormResponse {
  deleteLabel: boolean;
  labelID: number;
  label: string;
  cssClassName: string;
}

export class LabelManager {
  readonly #formLink: string;

  constructor(formLink: string) {
    this.#formLink = formLink;

    document.getElementById("addLabel")?.addEventListener(
      "click",
      promiseMutex(async () => {
        const { ok } = await dialogFactory().usingFormBuilder().fromEndpoint<LabelFormResponse>(this.#formLink);
        if (ok) {
          this.#refreshGridView();

          showDefaultSuccessSnackbar();
        }
      }),
    );

    wheneverFirstSeen(".editConversationLabel", (button) => {
      button.addEventListener(
        "click",
        promiseMutex(async () => {
          const row = button.closest<HTMLElement>(".gridView__row")!;
          const labelID = parseInt(row.dataset.objectId!, 10);

          const url = new URL(this.#formLink);
          url.searchParams.set("labelID", labelID.toString());

          const result = await dialogFactory().usingFormBuilder().fromEndpoint<LabelFormResponse>(url.toString());
          if (result.ok) {
            row.dispatchEvent(
              new CustomEvent("interaction:invalidate", {
                bubbles: true,
              }),
            );

            showDefaultSuccessSnackbar();
          }
        }),
      );
    });
  }

  #refreshGridView(): void {
    const gridView = document.getElementById("wcf-system-gridView-user-ConversationLabelGridView_table");
    gridView?.dispatchEvent(new CustomEvent("interaction:invalidate-all"));
  }
}
