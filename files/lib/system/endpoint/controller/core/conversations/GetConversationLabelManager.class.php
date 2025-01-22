<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\label\ConversationLabel;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * API endpoint for rendering the label manager dialog.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
#[GetRequest('/core/conversations/label-manager')]
final class GetConversationLabelManager implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        if (!WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        if (!WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }

        return new JsonResponse([
            'template' => WCF::getTPL()->fetch('conversationLabelManagement', 'wcf', [
                'cssClassNames' => ConversationLabel::getLabelCssClassNames(),
                'labelList' => ConversationLabel::getLabelsByUser(),
            ], true),
            'maxLabels' => WCF::getSession()->getPermission('user.conversation.maxLabels'),
            'labelCount' => \count(ConversationLabel::getLabelsByUser()),
        ]);
    }
}
