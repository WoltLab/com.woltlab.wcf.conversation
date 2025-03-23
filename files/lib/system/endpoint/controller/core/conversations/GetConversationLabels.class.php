<?php

namespace wcf\system\endpoint\controller\core\conversations;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use wcf\data\conversation\Conversation;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\http\Helper;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\endpoint\GetRequest;
use wcf\system\endpoint\IController;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\DialogFormDocument;
use wcf\system\form\builder\field\MultipleSelectionFormField;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * API endpoint for rendering the label selection form.
 *
 * @author      Olaf Braun
 * @copyright   2001-2025 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.2
 */
#[GetRequest('/core/conversations/labels')]
final class GetConversationLabels implements IController
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request, array $variables): ResponseInterface
    {
        $parameters = Helper::mapApiParameters($request, GetConversationLabelsParameters::class);
        $conversationIDs = $parameters->conversationIDs;

        if ($conversationIDs === []) {
            throw new IllegalLinkException();
        }
        if (!Conversation::isParticipant($conversationIDs)) {
            throw new PermissionDeniedException();
        }

        // Get the conversationID if only one conversation is selected
        // to preselect the label.
        $conversationID = null;
        if (\count($conversationIDs) === 1) {
            $conversationID = \reset($conversationIDs);
        }

        $labelList = ConversationLabel::getLabelsByUser();
        if (!\count($labelList)) {
            throw new IllegalLinkException();
        }
        $id = "conversationLabel";

        return new JsonResponse([
            'formId' => $id,
            'title' => WCF::getLanguage()->get('wcf.conversation.label.assignLabels'),
            'template' => $this->getForm($id, $labelList, $conversationID)->getHtml(),
        ]);
    }

    private function getForm(string $id, ConversationLabelList $labelList, ?int $conversationID): DialogFormDocument
    {
        return DialogFormDocument::create($id)
            ->ajax()
            ->prefix($id)
            ->appendChildren([
                MultipleSelectionFormField::create('labelIDs')
                    ->options(
                        \array_map(static function (ConversationLabel $label) {
                            return \sprintf(
                                '<span class="badge label%s">%s</span>',
                                empty($label->cssClassName) ? '' : ' ' . $label->cssClassName,
                                StringUtil::encodeHTML($label->label)
                            );
                        }, $labelList->getObjects())
                    )
                    ->value($this->getAssignedLabelIDs($labelList->getObjectIDs(), $conversationID)),
            ])
            ->addDefaultButton(false)
            ->build();
    }

    /**
     * @param int[] $labelIDs
     * @return int[]
     */
    private function getAssignedLabelIDs(array $labelIDs, ?int $conversationID): array
    {
        if ($conversationID === null) {
            return [];
        }

        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("conversationID = ?", [$conversationID]);
        $conditions->add("labelID IN (?)", [$labelIDs]);

        $sql = "SELECT  labelID
                FROM    wcf1_conversation_label_to_object
                " . $conditions;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute($conditions->getParameters());

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }
}

/** @internal */
final class GetConversationLabelsParameters
{
    public function __construct(
        /** @var array<positive-int> * */
        public readonly array $conversationIDs
    ) {
    }
}
