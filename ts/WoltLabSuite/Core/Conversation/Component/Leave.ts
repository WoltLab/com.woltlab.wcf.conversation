/**
 * Handles the leave conversation action.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { dialogFactory } from "WoltLabSuite/Core/Component/Dialog";

type Environment = "conversation" | "";

interface FormResult {
  redirectURL: string;
}

export async function openDialog(actionLink: string, environment: Environment) {
  const result = await dialogFactory().usingFormBuilder().fromEndpoint<FormResult>(actionLink);
  if (result.ok) {
    if (environment === "conversation") {
      window.location.href = result.result.redirectURL;
    } else {
      window.location.reload();
    }
  }
}
