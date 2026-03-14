<?php

namespace App\Tests\Form\Type;

use App\Form\Model\Campaign;
use App\Form\Model\SmsTrigger;
use App\Form\Type\CreateOrUseOperationType;
use Symfony\Component\Form\Test\TypeTestCase;

class CreateOrUseOperationTypeTest extends TypeTestCase
{
    public function testDataClassIsCampaign(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);
        $this->assertSame(Campaign::class, $form->getConfig()->getOption('data_class'));
    }

    public function testFormHasCreateOperationField(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);
        $this->assertTrue($form->has('createOperation'));
    }

    public function testFormHasContinueButton(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);
        $this->assertTrue($form->has('continue'));
    }

    public function testCreateOperationChoices(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);

        $choices = $form->get('createOperation')->getConfig()->getOption('choices');
        $this->assertContains(Campaign::CREATE_OPERATION, $choices);
        $this->assertContains(Campaign::USE_OPERATION, $choices);
    }

    public function testSubmitWithCreate(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);

        $form->submit([
            'createOperation' => Campaign::CREATE_OPERATION,
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(Campaign::CREATE_OPERATION, $form->getData()->createOperation);
    }

    public function testSubmitWithUse(): void
    {
        $campaign = $this->createCampaign();
        $form = $this->factory->create(CreateOrUseOperationType::class, $campaign);

        $form->submit([
            'createOperation' => Campaign::USE_OPERATION,
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(Campaign::USE_OPERATION, $form->getData()->createOperation);
    }

    private function createCampaign(): Campaign
    {
        return new Campaign(new SmsTrigger());
    }
}
