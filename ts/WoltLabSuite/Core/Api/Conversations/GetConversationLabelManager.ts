/**
 * Gets the data for the conversation label manager.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { prepareRequest } from "WoltLabSuite/Core/Ajax/Backend";
import { ApiResult, apiResultFromError, apiResultFromValue } from "WoltLabSuite/Core/Api/Result";

export type Response = {
  template: string;
  maxLabels: number;
  labelCount: number;
};

export async function getConversationLabelManager(): Promise<ApiResult<Response>> {
  let response: Response;
  try {
    response = (await prepareRequest(`${window.WSC_RPC_API_URL}core/conversations/label-manager`)
      .get()
      .fetchAsJson()) as Response;
  } catch (e) {
    return apiResultFromError(e);
  }

  return apiResultFromValue(response);
}
