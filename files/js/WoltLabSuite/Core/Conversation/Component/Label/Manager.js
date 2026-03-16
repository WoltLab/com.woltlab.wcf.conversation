/**
 * Managed the labels of a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Helper/PromiseMutex", "WoltLabSuite/Core/Component/Dialog", "WoltLabSuite/Core/Helper/Selector", "WoltLabSuite/Core/Component/Snackbar"], function (require, exports, PromiseMutex_1, Dialog_1, Selector_1, Snackbar_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.LabelManager = void 0;
    class LabelManager {
        #formLink;
        constructor(formLink) {
            this.#formLink = formLink;
            document.getElementById("addLabel")?.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                const { ok } = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(this.#formLink);
                if (ok) {
                    this.#refreshGridView();
                    (0, Snackbar_1.showDefaultSuccessSnackbar)();
                }
            }));
            (0, Selector_1.wheneverFirstSeen)(".editConversationLabel", (button) => {
                button.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                    const row = button.closest(".gridView__row");
                    const labelID = parseInt(row.dataset.objectId, 10);
                    const url = new URL(this.#formLink);
                    url.searchParams.set("labelID", labelID.toString());
                    const result = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(url.toString());
                    if (result.ok) {
                        row.dispatchEvent(new CustomEvent("interaction:invalidate", {
                            bubbles: true,
                        }));
                        (0, Snackbar_1.showDefaultSuccessSnackbar)();
                    }
                }));
            });
        }
        #refreshGridView() {
            const gridView = document.getElementById("wcf-system-gridView-user-ConversationLabelGridView_table");
            gridView?.dispatchEvent(new CustomEvent("interaction:invalidate-all"));
        }
    }
    exports.LabelManager = LabelManager;
});
