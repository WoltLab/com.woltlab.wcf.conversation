/**
 * User menu for notifications.
 *
 * @author Alexander Ebert
 * @copyright 2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module WoltLabSuite/Core/Ui/User/Menu/Data/Notification
 * @woltlabExcludeBundle tiny
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Ui/User/Menu/View", "WoltLabSuite/Core/Ui/User/Menu/Manager"], function (require, exports, tslib_1, Ajax_1, View_1, Manager_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = void 0;
    View_1 = tslib_1.__importDefault(View_1);
    class UserMenuDataConversation {
        constructor(button, options) {
            this.counter = 0;
            this.stale = true;
            this.view = undefined;
            this.button = button;
            this.options = options;
            const badge = button.querySelector(".badge");
            if (badge) {
                const counter = parseInt(badge.textContent.trim());
                if (counter) {
                    this.counter = counter;
                }
            }
        }
        getPanelButton() {
            return this.button;
        }
        getMenuButtons() {
            const buttons = [];
            if (this.options.canStartConversation) {
                buttons.push({
                    icon: '<span class="icon icon24 fa-plus"></span>',
                    link: this.options.newConversationLink,
                    name: "newConversation",
                    title: this.options.newConversationTitle,
                });
            }
            return buttons;
        }
        getIdentifier() {
            return "com.woltlab.wcf.conversation.conversations";
        }
        async getData() {
            const data = (await (0, Ajax_1.dboAction)("getConversations", "wcf\\data\\conversation\\ConversationAction")
                .disableLoadingIndicator()
                .dispatch());
            this.updateCounter(data.totalCount);
            this.stale = false;
            return data.items;
        }
        getFooter() {
            return {
                link: this.options.showAllLink,
                title: this.options.showAllTitle,
            };
        }
        getTitle() {
            return this.options.title;
        }
        getView() {
            if (this.view === undefined) {
                this.view = new View_1.default(this);
            }
            return this.view;
        }
        getEmptyViewMessage() {
            return this.options.noItems;
        }
        hasPlainTitle() {
            return true;
        }
        hasUnreadContent() {
            return this.counter > 0;
        }
        isStale() {
            if (this.stale) {
                return true;
            }
            const unreadItems = this.getView()
                .getItems()
                .filter((item) => item.dataset.isUnread === "true");
            if (this.counter !== unreadItems.length) {
                return true;
            }
            return false;
        }
        async markAsRead(objectId) {
            const response = (await (0, Ajax_1.dboAction)("markAsRead", "wcf\\data\\conversation\\ConversationAction")
                .objectIds([objectId])
                .dispatch());
            this.updateCounter(response.totalCount);
        }
        async markAllAsRead() {
            await (0, Ajax_1.dboAction)("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();
            this.updateCounter(0);
        }
        updateCounter(counter) {
            let badge = this.button.querySelector(".badge");
            if (badge === null && counter > 0) {
                badge = document.createElement("span");
                badge.classList.add("badge", "badgeUpdate");
                this.button.querySelector("a").append(badge);
            }
            if (badge) {
                if (counter === 0) {
                    badge.remove();
                }
                else {
                    badge.textContent = counter.toString();
                }
            }
            this.counter = counter;
        }
    }
    let isInitialized = false;
    function setup(options) {
        if (!isInitialized) {
            const button = document.getElementById("unreadConversations");
            if (button !== null) {
                const provider = new UserMenuDataConversation(button, options);
                (0, Manager_1.registerProvider)(provider);
            }
            isInitialized = true;
        }
    }
    exports.setup = setup;
});
