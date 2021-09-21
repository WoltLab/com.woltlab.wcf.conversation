define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Dialog", "WoltLabSuite/Core/Dom/Util", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Ui/Notification"], function (require, exports, tslib_1, Dialog_1, Util_1, Ajax, Language, UiNotification) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.beginEdit = void 0;
    Dialog_1 = (0, tslib_1.__importDefault)(Dialog_1);
    Util_1 = (0, tslib_1.__importDefault)(Util_1);
    Ajax = (0, tslib_1.__importStar)(Ajax);
    Language = (0, tslib_1.__importStar)(Language);
    UiNotification = (0, tslib_1.__importStar)(UiNotification);
    class UiSubjectEditor {
        constructor(objectId) {
            this.objectId = objectId;
        }
        /**
         * Shows the subject editor dialog.
         */
        show() {
            Dialog_1.default.open(this);
        }
        /**
         * Validates and saves the new subject.
         */
        saveEdit(event) {
            event.preventDefault();
            const value = this.subject.value.trim();
            if (value === "") {
                Util_1.default.innerError(this.subject, Language.get("wcf.global.form.error.empty"));
            }
            else {
                Util_1.default.innerError(this.subject, "");
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
        getCurrentValue() {
            return Array.from(document.querySelectorAll(`.jsConversationSubject[data-conversation-id="${this.objectId}"], .conversationLink[data-object-id="${this.objectId}"]`))
                .map((subject) => subject.textContent)
                .slice(-1)[0];
        }
        _ajaxSuccess(data) {
            Dialog_1.default.close(this);
            document
                .querySelectorAll(`.jsConversationSubject[data-conversation-id="${this.objectId}"], .conversationLink[data-object-id="${this.objectId}"]`)
                .forEach((subject) => {
                subject.textContent = data.returnValues.subject;
            });
            UiNotification.show();
        }
        _dialogSetup() {
            return {
                id: "dialogConversationSubjectEditor",
                options: {
                    onSetup: (content) => {
                        this.subject = document.getElementById("jsConversationSubject");
                        this.subject.addEventListener("keyup", (ev) => {
                            if (ev.key === "Enter") {
                                this.saveEdit(ev);
                            }
                        });
                        content.querySelector(".jsButtonSave").addEventListener("click", (ev) => this.saveEdit(ev));
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
        _ajaxSetup() {
            return {
                data: {
                    actionName: "editSubject",
                    className: "wcf\\data\\conversation\\ConversationAction",
                },
            };
        }
    }
    let editor;
    function beginEdit(objectId) {
        editor = new UiSubjectEditor(objectId);
        editor.show();
    }
    exports.beginEdit = beginEdit;
});
