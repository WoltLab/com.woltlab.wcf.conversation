/**
 * Remove a participant from a conversation.
 *
 * @author  Olaf Braun
 * @copyright  2001-2025 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */

import { prepareRequest } from "WoltLabSuite/Core/Ajax/Backend";
import { ApiResult, apiResultFromError, apiResultFromValue } from "WoltLabSuite/Core/Api/Result";

type Response = {
  template: string;
};

export async function removeParticipant(conversationId: number, participantId: number): Promise<ApiResult<string>> {
  let response: Response;
  try {
    response = (await prepareRequest(
      `${window.WSC_RPC_API_URL}core/conversations/${conversationId}/participants/${participantId}`,
    )
      .delete()
      .fetchAsJson()) as Response;
  } catch (e) {
    return apiResultFromError(e);
  }

  return apiResultFromValue(response.template);
}
