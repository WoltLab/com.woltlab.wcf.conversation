/**
 * Handles the leave conversation action.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
define(["require", "exports", "WoltLabSuite/Core/Component/Dialog"], function (require, exports, Dialog_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.openDialog = openDialog;
    async function openDialog(actionLink, environment) {
        const result = await (0, Dialog_1.dialogFactory)().usingFormBuilder().fromEndpoint(actionLink);
        if (result.ok) {
            if (environment === "conversation") {
                window.location.href = result.result.redirectURL;
            }
            else {
                window.location.reload();
            }
        }
    }
});
