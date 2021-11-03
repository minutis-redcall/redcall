<?php

namespace App\Manager;

use App\Base\BaseService;
use App\Entity\Answer;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Enum\Stop;
use App\Provider\SMS\SMSProvider;
use App\Repository\AnswerRepository;
use App\Security\Helper\Security;
use App\Services\MessageFormatter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnswerManager extends BaseService
{
    public static function getSubscribedServices()
    {
        return [
            AnswerRepository::class,
            CampaignManager::class,
            PhoneConfigManager::class,
            MessageFormatter::class,
            MessageManager::class,
            SMSProvider::class,
            TranslatorInterface::class,
            VolunteerManager::class,
            Security::class,
        ];
    }

    public function clearAnswers(Message $message)
    {
        $this->getAnswerRepository()->clearAnswers($message);
    }

    public function clearChoices(Message $message, array $choices)
    {
        $this->getAnswerRepository()->clearChoices($message, $choices);
    }

    public function save(Answer $answer)
    {
        $this->getAnswerRepository()->save($answer);
    }

    public function getSearchQueryBuilder(string $criteria) : QueryBuilder
    {
        return $this->getAnswerRepository()->getSearchQueryBuilder($criteria);
    }

    public function handleSpecialAnswers(Message $message, string $body)
    {
        if (Stop::isValid($body) && $message->getCommunication()->isSms()) {
            $volunteer = $message->getVolunteer();
            if (!$volunteer || !$volunteer->isPhoneNumberOptin()) {
                return;
            }

            $this->sendSms(
                $message,
                $this->getTranslator()->trans('special_answers.stop')
            );

            $volunteer->setPhoneNumberOptin(false);

            $this->getVolunteerManager()->save($volunteer);
        }
    }

    public function getVolunteerAnswersQueryBuilder(Volunteer $volunteer) : QueryBuilder
    {
        return $this->getAnswerRepository()->getVolunteerAnswersQueryBuilder($volunteer);
    }

    public function find(int $answerId) : ?Answer
    {
        return $this->getAnswerRepository()->find($answerId);
    }

    public function sendSms(Message $message, string $content)
    {
        $answer = new Answer();
        $answer->setMessage($message);
        $answer->setRaw($content);
        $answer->setReceivedAt(new \DateTime());
        $answer->setUnclear(true);

        $answer->setByAdmin('Robot');
        if ($this->getSecurity()->getUser()) {
            $answer->setByAdmin($this->getSecurity()->getUser()->getUserIdentifier());
        }

        $this->getAnswerRepository()->save($answer);

        $message->addAnswser($answer);
        $this->getMessageManager()->save($message);

        $country = $this->getPhoneConfigManager()->getPhoneConfigForVolunteer($message->getVolunteer());
        if ($country && $country->isOutboundSmsEnabled() && $country->getOutboundSmsNumber()) {
            $this->getSMSProvider()->send(
                $country->getOutboundSmsNumber(),
                $message->getVolunteer()->getPhoneNumber(),
                $this->getMessageFormatter()->formatSimpleSMSContent($message->getVolunteer(), $content),
                ['message_id' => $message->getId()]
            );
        }
    }

    private function getAnswerRepository() : AnswerRepository
    {
        return $this->get(AnswerRepository::class);
    }

    private function getCampaignManager() : CampaignManager
    {
        return $this->get(CampaignManager::class);
    }

    private function getPhoneConfigManager() : PhoneConfigManager
    {
        return $this->get(PhoneConfigManager::class);
    }

    private function getMessageFormatter() : MessageFormatter
    {
        return $this->get(MessageFormatter::class);
    }

    private function getMessageManager() : MessageManager
    {
        return $this->get(MessageManager::class);
    }

    private function getSMSProvider() : SMSProvider
    {
        return $this->get(SMSProvider::class);
    }

    private function getTranslator() : TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }
}