/**
 * Provides the editor for conversation subjects.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Core/Conversation/Ui/Subject/Editor
 */
import { DialogCallbackObject } from "WoltLabSuite/Core/Ui/Dialog/Data";
import UiDialog from "WoltLabSuite/Core/Ui/Dialog";
import DomUtil from "WoltLabSuite/Core/Dom/Util";
import * as Ajax from "WoltLabSuite/Core/Ajax";
import * as Language from "WoltLabSuite/Core/Language";
import { AjaxCallbackObject, ResponseData } from "WoltLabSuite/Core/Ajax/Data";
import * as UiNotification from "WoltLabSuite/Core/Ui/Notification";
import { DialogCallbackSetup } from "WoltLabSuite/Core/Ui/Dialog/Data";
import { AjaxCallbackSetup } from "WoltLabSuite/Core/Ajax/Data";

interface AjaxResponseData extends ResponseData {
  returnValues: {
    subject: string;
  };
}

class UiSubjectEditor implements AjaxCallbackObject, DialogCallbackObject {
  private readonly objectId: number;
  private subject: HTMLInputElement;

  constructor(objectId: number) {
    this.objectId = objectId;
  }

  /**
   * Shows the subject editor dialog.
   */
  public show(): void {
    UiDialog.open(this);
  }

  /**
   * Validates and saves the new subject.
   */
  protected saveEdit(event: Event) {
    event.preventDefault();

    const value = this.subject.value.trim();
    if (value === "") {
      DomUtil.innerError(this.subject, Language.get("wcf.global.form.error.empty"));
    } else {
      DomUtil.innerError(this.subject, "");

      Ajax.api(this, {
        parameters: {
          subject: value,
        },
        objectIDs: [this.objectId],
      });
    }
  }

  /**
   * Returns the current conversation subject.
   */
  protected getCurrentValue(): string {
    return Array.from(
      document.querySelectorAll(
        `.jsConversationSubject[data-conversation-id="${this.objectId}"], .conversationLink[data-object-id="${this.objectId}"]`,
      ),
    )
      .map((subject: HTMLElement) => subject.textContent!)
      .slice(-1)[0];
  }

  _ajaxSuccess(data: AjaxResponseData) {
    UiDialog.close(this);

    document
      .querySelectorAll(
        `.jsConversationSubject[data-conversation-id="${this.objectId}"], .conversationLink[data-object-id="${this.objectId}"]`,
      )
      .forEach((subject) => {
        subject.textContent = data.returnValues.subject;
      });

    UiNotification.show();
  }

  _dialogSetup(): ReturnType<DialogCallbackSetup> {
    return {
      id: "dialogConversationSubjectEditor",
      options: {
        onSetup: (content) => {
          this.subject = document.getElementById("jsConversationSubject") as HTMLInputElement;
          this.subject.addEventListener("keyup", (ev) => {
            if (ev.key === "Enter") {
              this.saveEdit(ev);
            }
          });

          content.querySelector(".jsButtonSave")!.addEventListener("click", (ev) => this.saveEdit(ev));
        },
        onShow: () => {
          this.subject.value = this.getCurrentValue();
        },
        title: Language.get("wcf.conversation.edit.subject"),
      },
      source: `
        <dl>
          <dt>
            <label for="jsConversationSubject">${Language.get("wcf.global.subject")}</label>
          </dt>
          <dd>
            <input type="text" id="jsConversationSubject" class="long" maxlength="255">
          </dd>
        </dl>
        <div class="formSubmit">
          <button class="buttonPrimary jsButtonSave">${Language.get("wcf.global.button.save")}</button>
        </div>
      `,
    };
  }

  _ajaxSetup(): ReturnType<AjaxCallbackSetup> {
    return {
      data: {
        actionName: "editSubject",
        className: "wcf\\data\\conversation\\ConversationAction",
      },
    };
  }
}

let editor: UiSubjectEditor;

export function beginEdit(objectId: number): void {
  editor = new UiSubjectEditor(objectId);

  editor.show();
}
