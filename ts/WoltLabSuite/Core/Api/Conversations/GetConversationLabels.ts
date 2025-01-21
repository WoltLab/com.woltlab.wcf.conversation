/**
 * Gets the form data to assign labels to conversations.
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
  formId: string;
  title: string;
};

export async function getConversationLabels(conversationIDs: number[]): Promise<ApiResult<Response>> {
  const url = new URL(`${window.WSC_RPC_API_URL}core/conversations/labels`);
  for (const conversationID of conversationIDs) {
    url.searchParams.append("conversationIDs[]", conversationID.toString());
  }

  let response: Response;
  try {
    response = (await prepareRequest(url).get().fetchAsJson()) as Response;
  } catch (e) {
    return apiResultFromError(e);
  }

  return apiResultFromValue(response);
}
