<?php

namespace App\Tests\Form\Type;

use App\Form\Model\BaseTrigger;
use App\Form\Model\SmsTrigger;
use App\Form\Type\ChooseOperationChoicesType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ChooseOperationChoicesTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testDataClassIsBaseTrigger(): void
    {
        $form = $this->factory->create(ChooseOperationChoicesType::class, $this->createTrigger());
        $this->assertSame(BaseTrigger::class, $form->getConfig()->getOption('data_class'));
    }

    public function testFormDynamicallyCreatesOperationAnswersField(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setAnswers(['Yes', 'No', 'Maybe']);

        $form = $this->factory->create(ChooseOperationChoicesType::class, $trigger);

        $this->assertTrue($form->has('operationAnswers'));
        $this->assertTrue($form->has('submit'));
    }

    public function testSubmitWithSelectedAnswers(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setAnswers(['Yes', 'No', 'Maybe']);

        $form = $this->factory->create(ChooseOperationChoicesType::class, $trigger);

        $form->submit([
            'operationAnswers' => ['Yes', 'Maybe'],
        ]);

        $this->assertTrue($form->isSynchronized());
    }

    private function createTrigger(): SmsTrigger
    {
        $trigger = new SmsTrigger();
        $trigger->setLabel('Test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Test message');

        return $trigger;
    }
}
