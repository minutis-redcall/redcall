<?php

namespace App\Manager;

use App\Entity\Volunteer;
use App\Model\PhoneConfig;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PhoneConfigManager
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getPhoneConfigForVolunteer(Volunteer $volunteer) : ?PhoneConfig
    {
        if (!$phone = $volunteer->getPhone()) {
            return null;
        }

        if (!$phone->getCountryCode()) {
            return null;
        }

        return $this->getPhoneConfig($volunteer->getPlatform(), $phone->getCountryCode());
    }

    public function getPhoneConfig(string $platform, string $countryCode)
    {
        $key       = strtolower(sprintf('%s_%s', $platform, $countryCode));
        $countries = array_change_key_case($this->parameterBag->get('phones'), CASE_LOWER);

        $country = $countries[$key] ?? null;
        if (!$country) {
            return null;
        }

        return $this->createCountryObject($country);
    }

    public function isSMSTransmittable(Volunteer $volunteer) : bool
    {
        if (!$country = $this->getPhoneConfigForVolunteer($volunteer)) {
            return false;
        }

        return $country->isOutboundSmsEnabled();
    }

    public function isVoiceCallTransmittable(Volunteer $volunteer) : bool
    {
        if (!$country = $this->getPhoneConfigForVolunteer($volunteer)) {
            return false;
        }

        return $country->isOutboundCallEnabled();
    }

    public function applyContext(PhoneConfig $country)
    {
        date_default_timezone_set($country->getTimezone());
    }

    public function restoreContext()
    {
        // In the application (see public/index.php), all dates are rendered and stored in Paris time.
        // In the long run, we'll need to store these dates in UTC with their timezone.
        // See: https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/cookbook/working-with-datetime.html
        date_default_timezone_set('Europe/Paris');
    }

    private function createCountryObject(array $row) : PhoneConfig
    {
        return new PhoneConfig(
            $row['timezone'],
            $row['outbound_call_enabled'],
            $row['outbound_call_number'],
            $row['outbound_sms_enabled'],
            $row['outbound_sms_short'],
            $row['outbound_sms_long'],
            $row['inbound_call_enabled'],
            $row['inbound_call_number'],
            $row['inbound_sms_enabled']
        );
    }
}