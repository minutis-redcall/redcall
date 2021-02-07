<?php

namespace App\Manager;

use App\Entity\Volunteer;
use App\Model\Country;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CountryManager
{
    /**
     * @var LocaleManager
     */
    private $localeManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @required
     */
    public function setLocaleManager(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;
    }

    public function getCountry(Volunteer $volunteer) : ?Country
    {
        if (!$phone = $volunteer->getPhone()) {
            return null;
        }

        $countries = array_change_key_case($this->parameterBag->get('countries'), CASE_LOWER);

        $country = $countries[strtolower($phone->getCountryCode())] ?? null;
        if (!$country) {
            return null;
        }

        return $this->createCountryObject($country);
    }

    /**
     * @return Country[]
     */
    public function getCountries() : array
    {
        $countries = [];

        foreach ($this->parameterBag->get('countries') as $key => $row) {
            $countries[$key] = $this->createCountryObject($row);
        }

        return $countries;
    }

    public function isSMSTransmittable(Volunteer $volunteer) : bool
    {
        if (!$country = $this->getCountry($volunteer)) {
            return false;
        }

        return $country->isOutboundSmsEnabled();
    }

    public function isVoiceCallTransmittable(Volunteer $volunteer) : bool
    {
        if (!$country = $this->getCountry($volunteer)) {
            return false;
        }

        return $country->isOutboundCallEnabled();
    }

    public function applyContext(Country $country)
    {
        $this->applyLocale($country);

        date_default_timezone_set($country->getTimezone());
    }

    public function applyLocale(?Country $country)
    {
        if ($country) {
            $this->localeManager->changeLocale($country->getLocale());
        }
    }

    public function restoreContext()
    {
        // In the application (see public/index.php), all dates are rendered and stored in Paris time.
        // In the long run, we'll need to store these dates in UTC with their timezone.
        // See: https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/cookbook/working-with-datetime.html
        date_default_timezone_set('Europe/Paris');
    }

    private function createCountryObject(array $row) : Country
    {
        return new Country(
            $row['locale'],
            $row['timezone'],
            $row['outbound_call_enabled'],
            $row['outbound_call_number'],
            $row['outbound_sms_enabled'],
            $row['outbound_sms_number'],
            $row['inbound_call_enabled'],
            $row['inbound_call_number'],
            $row['inbound_sms_enabled']
        );
    }
}