<?php

namespace App\Structure\Validation\Constraints;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class ParentStructure extends Constraint
{
    /** @var string */
    public $message = 'structure.parent_structure_not_found';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
