<?php

namespace wcf\system\endpoint\controller\core\conversations\labels;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelAction;
use wcf\http\Helper;
use wcf\system\endpoint\DeleteRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Deletes the conversation label with the given ID.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[DeleteRequest('/core/conversations/labels/{id:\d+}')]
final class DeleteConversationLabel implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $label = Helper::fetchObjectFromRequestParameter($variables['id'], ConversationLabel::class);
        $this->assertLabelCanBeDeleted($label);

        (new ConversationLabelAction([$label], 'delete'))->executeAction();

        return new JsonResponse([]);
    }

    private function assertLabelCanBeDeleted(ConversationLabel $label): void
    {
        if ($label->userID !== WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }
    }
}
