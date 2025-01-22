/**
 * Leaves a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { prepareRequest } from "WoltLabSuite/Core/Ajax/Backend";
import { ApiResult, apiResultFromError, apiResultFromValue } from "WoltLabSuite/Core/Api/Result";

type Response = {
  redirectUrl: string;
};

export type HideConversation = 0 | 1 | 2;

export async function leaveConversation(
  conversationId: number,
  hideConversation: HideConversation,
): Promise<ApiResult<string>> {
  let response: Response;
  try {
    response = (await prepareRequest(`${window.WSC_RPC_API_URL}core/conversations/${conversationId}/leave`)
      .post({
        hideConversation,
      })
      .fetchAsJson()) as Response;
  } catch (e) {
    return apiResultFromError(e);
  }

  return apiResultFromValue(response.redirectUrl);
}
