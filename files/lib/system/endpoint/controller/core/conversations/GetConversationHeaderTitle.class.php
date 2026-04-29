<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\system\cache\runtime\ConversationRuntimeCache;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Retrieves the HTML code for the content header title of the conversation with the given ID.
 *
 * @author Olaf Braun
 * @copyright 2001-2025 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.2
 */
#[GetRequest('/core/conversations/{id:\d+}/content-header-title')]
final class GetConversationHeaderTitle implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $conversation = ConversationRuntimeCache::getInstance()->getObject(\intval($variables['id']));
        if ($conversation === null) {
            throw new IllegalLinkException();
        }

        $this->assertConversationIsAccessible($conversation);

        return new JsonResponse([
            'template' => WCF::getTPL()->render('wcf', 'conversationContentHeaderTitle', [
                'conversation' => $conversation,
            ]),
        ]);
    }

    private function assertConversationIsAccessible(Conversation $conversation): void
    {
        if (!$conversation->canRead()) {
            throw new PermissionDeniedException();
        }
    }
}
