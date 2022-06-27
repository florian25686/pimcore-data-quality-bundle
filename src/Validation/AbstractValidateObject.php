<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Information\AbstractDefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\AbstractAttribute;

abstract class AbstractValidateObject implements ValidatableInterface, ScorableInterface, ColorableInterface
{
    use ColorScoreTrait;
    protected Concrete $obj;
    protected array $validationConfig;

    /**
     * Validators used for this object.
     *
     * @var AbstractAttribute[]
     */
    protected array $validators = [];
    protected AbstractDefinitionInformation $classInformation;
    protected array $skippedConstraints = [];

    /**
     * Validate an object and all its attributes.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected DefinitionInformationFactory $definitionInformationFactory,
        protected ContainerInterface $container,
        protected ConfigurationRepository $configurationRepository
    ) {
    }

    /**
     * Mark a constraint validator as skipped (useful to prevent recursion/cycles for relations).
     */
    public function addSkippedConstraint(string $constraintValidator): void
    {
        $this->skippedConstraints[] = $constraintValidator;
    }

    /**
     * Get the scores for the individual attributes.
     */
    public function attributeScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $attributeScores[$attribute]['color'] = null;
            $attributeScores[$attribute]['colors'] = [];
            $attributeScores[$attribute]['score'] = null;
            $attributeScores[$attribute]['scores'] = [];
            $attributeScores[$attribute]['value'] = $validator->value();

            if ($validator instanceof ScorableInterface) {
                $attributeScores[$attribute]['score'] = $validator->score();
            }

            if ($validator instanceof MultiScorableInterface) {
                $attributeScores[$attribute]['scores'] = $validator->scores();
            }

            if ($validator instanceof ColorableInterface) {
                $attributeScores[$attribute]['color'] = $validator->color();
            }

            if ($validator instanceof MultiColorableInterface) {
                $attributeScores[$attribute]['colors'] = $validator->colors();
            }
        }

        return $attributeScores;
    }

    /**
     * Set the object to validate.
     *
     * @param Concrete $obj the object to validate
     */
    abstract public function setObject(Concrete $obj): void;

    /**
     * Returns a list of all attributes that can be validated i.e. that exist and are configured.
     */
    protected function getValidatableAttributes(): array
    {
        return array_intersect($this->getAttributesInConfig(), $this->getAttributesInObject());
    }

    /**
     * Returns a list of configured attributes.
     */
    protected function getAttributesInConfig(): array
    {
        return array_keys($this->validationConfig);
    }

    /**
     * Returns a list of attributes present in the object.
     */
    protected function getAttributesInObject(): array
    {
        return array_keys($this->classInformation->getAllAttributes());
    }
}
