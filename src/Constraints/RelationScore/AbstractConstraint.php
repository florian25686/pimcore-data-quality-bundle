<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

abstract class AbstractConstraint extends AbstractCustomConstraint
{
    /**
     * @var string
     */
    public $message = 'The related object score(s) fall below the threshold (IDs: {{ ids }}).';

    /**
     * @var ContainerInterface
     */
    public $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
