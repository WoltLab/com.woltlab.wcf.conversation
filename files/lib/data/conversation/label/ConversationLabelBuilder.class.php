<?php

namespace wcf\data\conversation\label;

use wcf\data\DatabaseObjectBuilder;

/**
 * Builder for creating, updating and deleting conversation labels.
 *
 * @author      Marcel Werk
 * @copyright   2001-2026 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       6.3
 *
 * @extends DatabaseObjectBuilder<ConversationLabel>
 */
final class ConversationLabelBuilder extends DatabaseObjectBuilder
{
    public function setUserID(int $userID): static
    {
        $this->properties['userID'] = $userID;

        return $this;
    }

    public function setLabel(string $label): static
    {
        $this->properties['label'] = $label;

        return $this;
    }

    public function setCssClassName(string $cssClassName): static
    {
        $this->properties['cssClassName'] = $cssClassName;

        return $this;
    }

    #[\Override]
    protected function getRequiredProperties(): array
    {
        return ['userID', 'label'];
    }
}
