<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

interface Colorable extends BaseColorable
{
    /**
     * Returns a color (red, orange, green) depending on the class configuration
     * and the score of the object being validated.
     */
    public function color(): string;
}
