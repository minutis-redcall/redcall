<?php

namespace App\Manager;

use App\Base\BaseService;
use App\Entity\Answer;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Enum\Stop;
use App\Enum\Type;
use App\Provider\SMS\SMSProvider;
use App\Repository\AnswerRepository;
use App\Services\MessageFormatter;
use Doctrine\ORM\QueryBuilder;
use Google\Cloud\Language\LanguageClient;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnswerManager extends BaseService
{
    public static function getSubscribedServices()
    {
        return [
            AnswerRepository::class,
            CampaignManager::class,
            PhoneConfigManager::class,
            LoggerInterface::class,
            MessageFormatter::class,
            MessageManager::class,
            SMSProvider::class,
            TranslatorInterface::class,
            UserManager::class,
            VolunteerManager::class,
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

    public function handleSpecialAnswers(string $phoneNumber, string $body)
    {
        if (Stop::isValid($body)) {
            $volunteer = $this - $this->getVolunteerManager()->findOneByPhoneNumber($phoneNumber);
            if (!$volunteer || !$volunteer->isPhoneNumberOptin()) {
                return;
            }

            $this->getCampaignManager()->contact(
                $volunteer,
                Type::SMS(),
                $this->getTranslator()->trans('special_answers.title', [
                    '%keyword%' => $body,
                ]),
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
        $answer->setByAdmin($this->getUserManager()->findForCurrentUser()->getUsername());

        $this->getAnswerRepository()->save($answer);

        $message->addAnswser($answer);
        $this->getMessageManager()->save($message);

        $country = $this->getPhoneConfigManager()->getPhoneConfig($message->getVolunteer());
        if ($country && $country->isOutboundSmsEnabled() && $country->getOutboundSmsNumber()) {
            $this->getSMSProvider()->send(
                $country->getOutboundSmsNumber(),
                $message->getVolunteer()->getPhoneNumber(),
                $this->getMessageFormatter()->formatSimpleSMSContent($message->getVolunteer(), $content),
                ['message_id' => $message->getId()]
            );
        }
    }

    public function addSentiment(Answer $answer, string $body)
    {
        try {
            $client     = new LanguageClient();
            $annotation = $client->analyzeSentiment($body);
            $sentiment  = $annotation->sentiment();

            $answer->setSentiment((int) ($sentiment['score'] * 100));
            $answer->setMagnitude((int) ($sentiment['magnitude'] * 100));
        } catch (\Throwable $e) {
            $this->getLogger()->warning('Cannot retrieve answer\'s sentiment', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
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

    private function getLogger() : LoggerInterface
    {
        return $this->get(LoggerInterface::class);
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

    private function getUserManager() : UserManager
    {
        return $this->get(UserManager::class);
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }
}