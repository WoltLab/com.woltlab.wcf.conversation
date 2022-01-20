/**
 * User menu for notifications.
 *
 * @author Alexander Ebert
 * @copyright 2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module WoltLabSuite/Core/Ui/User/Menu/Data/Notification
 * @woltlabExcludeBundle tiny
 */

import { dboAction } from "WoltLabSuite/Core/Ajax";
import UserMenuView from "WoltLabSuite/Core/Ui/User/Menu/View";
import {
  UserMenuButton,
  UserMenuData,
  UserMenuFooter,
  UserMenuProvider,
} from "WoltLabSuite/Core/Ui/User/Menu/Data/Provider";
import { registerProvider } from "WoltLabSuite/Core/Ui/User/Menu/Manager";

type Options = {
  canStartConversation: boolean;
  newConversationLink: string;
  newConversationTitle: string;
  noItems: string;
  showAllLink: string;
  showAllTitle: string;
  title: string;
};

type ResponseMarkAsRead = {
  markAsRead: number;
  totalCount: number;
};

class UserMenuDataConversation implements UserMenuProvider {
  private readonly button: HTMLElement;
  private counter = 0;
  private readonly options: Options;
  private stale = true;
  private view: UserMenuView | undefined = undefined;

  constructor(button: HTMLElement, options: Options) {
    this.button = button;
    this.options = options;

    const badge = button.querySelector<HTMLElement>(".badge");
    if (badge) {
      const counter = parseInt(badge.textContent!.trim());
      if (counter) {
        this.counter = counter;
      }
    }

    window.WCF.System.PushNotification.addCallback("userNotificationCount", (counter: number) => {
      this.updateCounter(counter);

      this.stale = true;
    });
  }

  getPanelButton(): HTMLElement {
    return this.button;
  }

  getMenuButtons(): UserMenuButton[] {
    const buttons: UserMenuButton[] = [];
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

  getIdentifier(): string {
    return "com.woltlab.wcf.conversation.conversations";
  }

  async getData(): Promise<UserMenuData[]> {
    const data = (await dboAction(
      "getConversations",
      "wcf\\data\\conversation\\ConversationAction",
    ).dispatch()) as UserMenuData[];

    const counter = data.filter((item) => item.isUnread).length;
    this.updateCounter(counter);

    this.stale = false;

    return data;
  }

  getFooter(): UserMenuFooter | null {
    return {
      link: this.options.showAllLink,
      title: this.options.showAllTitle,
    };
  }

  getTitle(): string {
    return this.options.title;
  }

  getView(): UserMenuView {
    if (this.view === undefined) {
      this.view = new UserMenuView(this);
    }

    return this.view;
  }

  getEmptyViewMessage(): string {
    return this.options.noItems;
  }

  hasUnreadContent(): boolean {
    return this.counter > 0;
  }

  isStale(): boolean {
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

  async markAsRead(objectId: number): Promise<void> {
    const response = (await dboAction("markAsRead", "wcf\\data\\conversation\\ConversationAction")
      .objectIds([objectId])
      .dispatch()) as ResponseMarkAsRead;

    this.updateCounter(response.totalCount);
  }

  async markAllAsRead(): Promise<void> {
    await dboAction("markAllAsRead", "wcf\\data\\conversation\\ConversationAction").dispatch();
  }

  private updateCounter(counter: number): void {
    let badge = this.button.querySelector<HTMLElement>(".badge");
    if (badge === null && counter > 0) {
      badge = document.createElement("span");
      badge.classList.add("badge badgeUpdate");

      this.button.querySelector("a")!.append(badge);
    }

    if (badge) {
      if (counter === 0) {
        badge.remove();
      } else {
        badge.textContent = counter.toString();
      }
    }

    this.counter = counter;
  }
}

let isInitialized = false;
export function setup(options: Options): void {
  if (!isInitialized) {
    const button = document.getElementById("unreadConversations");
    if (button !== null) {
      const provider = new UserMenuDataConversation(button, options);
      registerProvider(provider);
    }

    isInitialized = true;
  }
}
