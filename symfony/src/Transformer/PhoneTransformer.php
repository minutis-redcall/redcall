<?php

namespace App\Transformer;

use App\Entity\Phone;
use App\Facade\Phone\PhoneFacade;
use App\Facade\Phone\PhoneReadFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class PhoneTransformer extends BaseTransformer
{
    /**
     * @param Phone $object
     *
     * @return PhoneFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $object->populateFromE164();

        $facade = new PhoneReadFacade();
        $facade->setPreferred($object->isPreferred());
        $facade->setE164($object->getE164());
        $facade->setCountryCode($object->getCountryCode());
        $facade->setPrefix($object->getPrefix());
        $facade->setNationalNumber($object->getNational());
        $facade->setInternationalNumber($object->getInternational());
        $facade->setMobile($object->isMobile());

        return $facade;
    }

    /**
     * @param PhoneFacade $facade
     * @param Phone|null  $object
     *
     * @return Phone
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        $phone = $object;
        if (!$phone) {
            $phone = new Phone();
        }

        if (null !== $facade->getE164()) {
            $phone->setE164($facade->getE164());
        }

        if (null !== $facade->isPreferred()) {
            $phone->setPreferred($facade->isPreferred());
        }

        return $phone;
    }
}