<?php

namespace Drupal\wmdummy_data;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmcontent\Entity\WmContentContainer;

interface ContentGenerateInterface
{
    /**
     * Generate content for a wmcontent container. This method will be called for
     * every parent container of this entity. If no children should be generated,
     * just return an empty array.
     *
     * @return EntityInterface[]
     */
    public function generateContent(WmContentContainer $container): array;
}
