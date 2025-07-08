/**
 * Managed the labels of a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "../../../Api/Conversations/GetConversationLabelManager", "WoltLabSuite/Core/Helper/PromiseMutex", "WoltLabSuite/Core/Component/Dialog", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Component/Snackbar"], function (require, exports, GetConversationLabelManager_1, PromiseMutex_1, Dialog_1, Language_1, Snackbar_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.LabelManager = void 0;
    class LabelManager {
        #formLink;
        #dialog;
        #maxLabels = 0;
        #labelCount = 0;
        #listViewId;
        constructor(listViewId, formLink) {
            this.#formLink = formLink;
            this.#listViewId = listViewId;
            document.getElementById("manageLabel")?.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                const response = await (0, GetConversationLabelManager_1.getConversationLabelManager)();
                if (!response.ok) {
                    throw new Error("Could not fetch conversation label manager");
                }
                this.#openDialog(response.value);
            }));
        }
        #openDialog(data) {
            this.#maxLabels = data.maxLabels;
            this.#labelCount = data.labelCount;
            this.#dialog = (0, Dialog_1.dialogFactory)().fromHtml(data.template).withoutControls();
            this.#updateAddButtonState();
            this.#dialog.show((0, Language_1.getPhrase)("wcf.conversation.label.management"));
            this.#dialog?.content.querySelectorAll(".conversationLabelList .badge").forEach((badge) => {
                const labelId = parseInt(badge.dataset.labelId);
                badge.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                    await this.#openForm(labelId);
                }));
            });
            this.#dialog?.content.querySelector(".addLabel")?.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(async () => {
                await this.#openForm();
            }));
        }
        async #openForm(labelId) {
            const url = new URL(this.#formLink);
            if (labelId) {
                url.searchParams.set("labelID", labelId.toString());
            }
            const response = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(url.toString());
            if (response.ok) {
                if (response.result.deleteLabel) {
                    const button = document.querySelector(`#${this.#listViewId}_filters .button[data-filter="label"][data-filter-value="${response.result.labelID}"]`);
                    if (button) {
                        button.dispatchEvent(new Event("click", { bubbles: true, cancelable: true }));
                    }
                    else {
                        this.#reloadListView();
                    }
                }
                else if (labelId) {
                    this.#reloadListView();
                }
                this.#dialog?.close();
                (0, Snackbar_1.showDefaultSuccessSnackbar)();
            }
        }
        #reloadListView() {
            const listView = document.getElementById(`${this.#listViewId}_items`);
            listView.dispatchEvent(new CustomEvent("interaction:invalidate-all"));
        }
        #updateAddButtonState() {
            this.#dialog.content.querySelector(".addLabel").disabled = this.#labelCount >= this.#maxLabels;
        }
    }
    exports.LabelManager = LabelManager;
});
