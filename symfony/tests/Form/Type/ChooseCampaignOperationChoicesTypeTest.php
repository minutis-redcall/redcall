<?php

namespace App\Tests\Form\Type;

use App\Form\Model\Campaign;
use App\Form\Model\SmsTrigger;
use App\Form\Type\ChooseCampaignOperationChoicesType;
use App\Form\Type\ChooseOperationChoicesType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ChooseCampaignOperationChoicesTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testDataClassIsCampaign(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(ChooseCampaignOperationChoicesType::class, $campaign);
        $this->assertSame(Campaign::class, $form->getConfig()->getOption('data_class'));
    }

    public function testFormHasTriggerField(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(ChooseCampaignOperationChoicesType::class, $campaign);
        $this->assertTrue($form->has('trigger'));
    }

    public function testTriggerFieldTypeIsChooseOperationChoicesType(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(ChooseCampaignOperationChoicesType::class, $campaign);
        $triggerField = $form->get('trigger');

        $innerType = $triggerField->getConfig()->getType()->getInnerType();
        $this->assertInstanceOf(ChooseOperationChoicesType::class, $innerType);
    }

    private function createCampaign(): Campaign
    {
        $trigger = new SmsTrigger();
        $trigger->setAnswers(['Yes', 'No']);
        $trigger->setLabel('Test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Test message');

        return new Campaign($trigger);
    }
}
