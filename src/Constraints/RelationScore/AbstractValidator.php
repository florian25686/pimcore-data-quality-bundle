<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Valantic\DataQualityBundle\Validation\Colorable;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

abstract class AbstractValidator extends ConstraintValidator
{
    /**
     * @var Validate
     */
    protected $validate;

    /**
     * Get the Colorable threshold for this validator.
     * @return string
     */
    abstract protected function getThresholdKey(): string;

    /**
     * Get the constraint this validator expects.
     * @return string
     */
    abstract protected function getConstraint(): string;

    /**
     * Validation passes if all relations have a green score.
     *
     * @param mixed $value
     * @param Constraint $constraint
     *
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {
        if (get_class($constraint) !== $this->getConstraint()) {
            throw new UnexpectedTypeException($constraint, $this->getConstraint());
        }

        $this->validate = $constraint->container->get('valantic_dataquality_validate_dataobject');

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $validCount = 0;
        $totalCount = 0;

        foreach ($value as $id) {
            $validation = clone $this->validate;
            $validation->setObject(Concrete::getById($id));
            $validation->addSkippedConstraint(AbstractConstraint::class);
            $validation->validate();
            $totalCount++;

            if ($validation->color() === $this->getThresholdKey() || ($this->getThresholdKey() === Colorable::COLOR_ORANGE && $validation->color() === Colorable::COLOR_GREEN)) {
                $validCount++;
            }
        }

        if ($validCount !== $totalCount) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
