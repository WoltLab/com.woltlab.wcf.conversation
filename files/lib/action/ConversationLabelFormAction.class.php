<?php

namespace wcf\action;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelAction;
use wcf\data\IStorableObject;
use wcf\http\Helper;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BadgeColorFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\WCF;

/**
 * Action for adding/editing conversation labels.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
final class ConversationLabelFormAction implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = Helper::mapQueryParameters(
            $request->getQueryParams(),
            <<<'EOT'
                array {
                    labelID?: positive-int
                }
                EOT
        );

        if (WCF::getUser()->isGuest()) {
            throw new PermissionDeniedException();
        }
        if (!WCF::getSession()->hasPermission('user.conversation.canUseConversation')) {
            throw new PermissionDeniedException();
        }

        $label = null;
        if (isset($parameters['labelID'])) {
            $label = new ConversationLabel($parameters['labelID']);
            if ($label->userID !== WCF::getUser()->userID) {
                throw new PermissionDeniedException();
            }
        } elseif (
            \count(ConversationLabel::getUserLabels())
            >= WCF::getSession()->getPermission('user.conversation.maxLabels')
        ) {
            throw new NamedUserException(WCF::getLanguage()->get('wcf.conversation.label.management.addLabel.maxLabels'));
        }

        $form = $this->getForm($label);

        if ($request->getMethod() === 'GET') {
            return $form->toResponse();
        } elseif ($request->getMethod() === 'POST') {
            $response = $form->validateRequest($request);
            if ($response !== null) {
                return $response;
            }

            $data = $form->getData()['data'];

            if ($label !== null) {
                (new ConversationLabelAction([$label], 'update', [
                    'data' => $data,
                ]))->executeAction();
            } else {
                (new ConversationLabelAction([], 'create', [
                    'data' => \array_merge($data, [
                        'userID' => WCF::getUser()->userID,
                    ]),
                ]))->executeAction();
            }

            return new JsonResponse([
                'result' => [],
            ]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    private function getForm(?ConversationLabel $label): Psr15DialogForm
    {
        $form = new Psr15DialogForm(
            self::class,
            $label !== null ? WCF::getLanguage()->getDynamicVariable('wcf.conversation.label.management.editLabel', [
                'labelName' => $label->label,
            ]) : WCF::getLanguage()->get('wcf.conversation.label.management.addLabel')
        );
        $labelFormField = TextFormField::create('label')
            ->label('wcf.conversation.label.labelName')
            ->maximumLength(80)
            ->required();

        $cssClassNameFormField = BadgeColorFormField::create('cssClassName')
            ->supportCustomClassName(false)
            ->label('wcf.conversation.label.cssClassName')
            ->textReferenceNodeId($form->getPrefix() . 'label')
            ->defaultLabelText(WCF::getLanguage()->get('wcf.conversation.label.placeholder'))
            ->value('none')
            ->required();

        $form->appendChildren([
            $labelFormField,
            $cssClassNameFormField,
        ]);

        // Map the pseudo value 'none' to an empty string
        $form->getDataHandler()->addProcessor(
            new CustomFormDataProcessor(
                'cssClassNameProcessor',
                static function (IFormDocument $document, array $parameters) {
                    if (isset($parameters['data']['cssClassName']) && $parameters['data']['cssClassName'] === 'none') {
                        $parameters['data']['cssClassName'] = '';
                    }

                    return $parameters;
                },
                static function (IFormDocument $document, array $data, IStorableObject $object) {
                    if ($data['cssClassName'] === '') {
                        $data['cssClassName'] = 'none';
                    }

                    return $data;
                }
            )
        );

        $form->markRequiredFields(false);
        if ($label !== null) {
            $form->updatedObject($label);
        }
        $form->build();

        return $form;
    }
}
