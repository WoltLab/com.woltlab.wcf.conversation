/**
 * Adds participants to an existing conversation.
 *
 * @author  Alexander Ebert
 * @copyright   2001-2021 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

import * as Ajax from "WoltLabSuite/Core/Ajax";
import { AjaxCallbackObject } from "WoltLabSuite/Core/Ajax/Data";
import { AjaxCallbackSetup, ResponseData } from "WoltLabSuite/Core/Ajax/Data";
import DomUtil from "WoltLabSuite/Core/Dom/Util";
import UiDialog from "WoltLabSuite/Core/Ui/Dialog";
import * as UiNotification from "WoltLabSuite/Core/Ui/Notification";
import { DialogCallbackObject, DialogCallbackSetup } from "WoltLabSuite/Core/Ui/Dialog/Data";
import * as UiItemListUser from "WoltLabSuite/Core/Ui/ItemList/User";
import { ItemData } from "WoltLabSuite/Core/Ui/ItemList";
import * as Language from "WoltLabSuite/Core/Language";

interface AjaxResponseData extends ResponseData {
  actionName: "addParticipants" | "getAddParticipantsForm";
}

interface AjaxAddParticipantsData extends AjaxResponseData {
  returnValues:
    | {
        errorMessage: string;
      }
    | {
        count: number;
        successMessage: string;
      };
}

interface AjaxGetAddParticipantsFormData extends AjaxResponseData {
  returnValues: {
    canAddGroupParticipants: boolean;
    csvPerType: boolean;
    excludedSearchValues: string[];
    maxItems: number;
    restrictUserGroupIDs: number[];
    template: string;
  };
}

class UiParticipantAdd implements AjaxCallbackObject, DialogCallbackObject {
  protected readonly conversationId: number;

  constructor(conversationId: number) {
    this.conversationId = conversationId;

    Ajax.api(this, {
      actionName: "getAddParticipantsForm",
    });
  }

  _ajaxSetup(): ReturnType<AjaxCallbackSetup> {
    return {
      data: {
        className: "wcf\\data\\conversation\\ConversationAction",
        objectIDs: [this.conversationId],
      },
    };
  }

  _ajaxSuccess(data: AjaxResponseData): void {
    switch (data.actionName) {
      case "addParticipants":
        this.handleResponse(data as AjaxAddParticipantsData);
        break;
      case "getAddParticipantsForm":
        this.render(data as AjaxGetAddParticipantsFormData);
        break;
    }
  }

  /**
   * Shows the success message and closes the dialog overlay.
   */
  protected handleResponse(data: AjaxAddParticipantsData): void {
    if ("errorMessage" in data.returnValues) {
      DomUtil.innerError(
        document.getElementById("participantsInput")!.closest(".inputItemList")!,
        data.returnValues.errorMessage,
      );
      return;
    }

    if ("count" in data.returnValues) {
      UiNotification.show(data.returnValues.successMessage, () => {
        window.location.reload();
      });
    }

    UiDialog.close(this);
  }

  /**
   * Renders the dialog to add participants.
   * @protected
   */
  protected render(data: AjaxGetAddParticipantsFormData): void {
    UiDialog.open(this, data.returnValues.template);

    const buttonSubmit = document.getElementById("addParticipants") as HTMLButtonElement;
    buttonSubmit.disabled = true;

    UiItemListUser.init("participantsInput", {
      callbackChange: (elementId: string, values: ItemData[]): void => {
        buttonSubmit.disabled = values.length === 0;
      },
      excludedSearchValues: data.returnValues.excludedSearchValues,
      maxItems: data.returnValues.maxItems,
      includeUserGroups: data.returnValues.canAddGroupParticipants && data.returnValues.restrictUserGroupIDs.length > 0,
      restrictUserGroupIDs: data.returnValues.restrictUserGroupIDs,
      csvPerType: true,
    });
    buttonSubmit.addEventListener("click", () => {
      this.submit();
    });
  }

  /**
   * Sends a request to add participants.
   */
  protected submit(): void {
    const participants: string[] = [];
    const participantsGroupIDs: number[] = [];
    UiItemListUser.getValues("participantsInput").forEach((value) => {
      if (value.type === "group") {
        participantsGroupIDs.push(value.objectId);
      } else {
        participants.push(value.value);
      }
    });

    const parameters = {
      participants: participants,
      participantsGroupIDs: participantsGroupIDs,
      visibility: null as null | string,
    };
    const visibility = UiDialog.getDialog(this)!.content.querySelector<HTMLInputElement>(
      'input[name="messageVisibility"]:checked, input[name="messageVisibility"][type="hidden"]',
    );

    if (visibility) {
      parameters.visibility = visibility.value;
    }

    Ajax.api(this, {
      actionName: "addParticipants",
      parameters: parameters,
    });
  }

  _dialogSetup(): ReturnType<DialogCallbackSetup> {
    return {
      id: "conversationAddParticipants",
      options: {
        title: Language.get("wcf.conversation.edit.addParticipants"),
      },
      source: null,
    };
  }
}

export = UiParticipantAdd;
