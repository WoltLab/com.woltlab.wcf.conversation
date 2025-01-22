/**
 * Managed the labels of a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "tslib", "../../../Api/Conversations/GetConversationLabelManager", "WoltLabSuite/Core/Helper/PromiseMutex", "WoltLabSuite/Core/Component/Dialog", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Ui/Dropdown/Simple"], function (require, exports, tslib_1, GetConversationLabelManager_1, PromiseMutex_1, Dialog_1, Language_1, Notification_1, Simple_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.LabelManager = void 0;
    Simple_1 = tslib_1.__importDefault(Simple_1);
    class LabelManager {
        #conversationListLink;
        #formLink;
        #dialog;
        #maxLabels = 0;
        #labelCount = 0;
        constructor(formLink, conversationListLink) {
            this.#formLink = formLink;
            this.#conversationListLink = conversationListLink;
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
                button.addEventListener("click", (0, PromiseMutex_1.promiseMutex)(() => this.#openForm(response.result.labelID)));
                const li = document.createElement("li");
                li.append(button);
                this.#dialog?.content.querySelector(".conversationLabelList")?.append(li);
                this.#insertLabel(response.result);
                this.#updateAddButtonState();
                (0, Notification_1.show)();
            }
        }
        #updateAddButtonState() {
            this.#dialog.content.querySelector(".addLabel").disabled = this.#labelCount >= this.#maxLabels;
        }
        #insertLabel(data) {
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
            Simple_1.default.getDropdownMenu("conversationLabelFilter")
                ?.querySelector(".scrollableDropdownMenu")
                ?.append(listItem);
        }
    }
    exports.LabelManager = LabelManager;
});
