<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Tool;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Validation\MultiColorable;
use Valantic\DataQualityBundle\Validation\MultiScorable;

class LocalizedAttribute extends AbstractAttribute implements MultiScorable, MultiColorable
{
    public function validate(): void
    {
        if (!$this->classInformation->isLocalizedAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->getValidatableLocales() as $locale) {
                $this->violations[$locale] = $this->validator->validate($this->value()[$locale], $this->getConstraints());
            }
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    public function score(): float
    {
        if (!count($this->getConstraints()) || !count($this->getValidatableLocales())) {
            return 0;
        }

        return array_sum($this->scores()) / count($this->getValidatableLocales());
    }

    public function scores(): array
    {
        if (!count($this->getConstraints())) {
            return [];
        }

        $scores = [];

        foreach ($this->getValidatableLocales() as $locale) {
            $scores[$locale] = 1 - (count($this->violations[$locale]) / count($this->getConstraints()));
        }

        return $scores;
    }

    public function colors(): array
    {
        if (!count($this->getConstraints())) {
            return [];
        }

        $scores = $this->scores();
        $colors = [];

        foreach ($this->getValidatableLocales() as $locale) {
            $colors[$locale] = $this->calculateColor($scores[$locale]);
        }

        return $colors;
    }

    public function value(): mixed
    {
        $value = [];

        foreach ($this->getValidatableLocales() as $locale) {
            try {
                $value[$locale] = $this->valueInherited($this->obj, $locale);
            } catch (Throwable) {
                continue;
            }
        }

        return $value;
    }

    protected function getValidatableLocales(): array
    {
        return array_intersect($this->configurationRepository->getConfiguredLocales($this->obj::class), $this->getValidLocales());
    }

    /**
     * List of enabled locales.
     */
    protected function getValidLocales(): array
    {
        return Tool::getValidLanguages();
    }
}
