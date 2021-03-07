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
            CountryManager::class,
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
        $this->get(AnswerRepository::class)->clearAnswers($message);
    }

    public function clearChoices(Message $message, array $choices)
    {
        $this->get(AnswerRepository::class)->clearChoices($message, $choices);
    }

    public function save(Answer $answer)
    {
        $this->get(AnswerRepository::class)->save($answer);
    }

    public function getSearchQueryBuilder(string $criteria) : QueryBuilder
    {
        return $this->get(AnswerRepository::class)->getSearchQueryBuilder($criteria);
    }

    public function handleSpecialAnswers(string $phoneNumber, string $body)
    {
        if (Stop::isValid($body)) {
            $volunteer = $this->get(VolunteerManager::class)->findOneByPhoneNumber($phoneNumber);
            if (!$volunteer || !$volunteer->isPhoneNumberOptin()) {
                return;
            }

            $this->get(CampaignManager::class)->contact(
                $volunteer,
                Type::SMS(),
                $this->get(TranslatorInterface::class)->trans('special_answers.title', [
                    '%keyword%' => $body,
                ]),
                $this->get(TranslatorInterface::class)->trans('special_answers.stop')
            );

            $volunteer->setPhoneNumberOptin(false);

            $this->get(VolunteerManager::class)->save($volunteer);
        }
    }

    public function getVolunteerAnswersQueryBuilder(Volunteer $volunteer) : QueryBuilder
    {
        return $this->get(AnswerRepository::class)->getVolunteerAnswersQueryBuilder($volunteer);
    }

    public function find(int $answerId) : ?Answer
    {
        return $this->get(AnswerRepository::class)->find($answerId);
    }

    public function sendSms(Message $message, string $content)
    {
        $answer = new Answer();
        $answer->setMessage($message);
        $answer->setRaw($content);
        $answer->setReceivedAt(new \DateTime());
        $answer->setUnclear(true);
        $answer->setByAdmin($this->get(UserManager::class)->findForCurrentUser()->getUsername());

        $this->get(AnswerRepository::class)->save($answer);

        $message->addAnswser($answer);
        $this->get(MessageManager::class)->save($message);

        $country = $this->get(CountryManager::class)->getCountry($message->getVolunteer());
        if ($country && $country->isOutboundSmsEnabled() && $country->getOutboundSmsNumber()) {
            $this->get(SMSProvider::class)->send(
                $country->getOutboundSmsNumber(),
                $message->getVolunteer()->getPhoneNumber(),
                $this->get(MessageFormatter::class)->formatSimpleSMSContent($message->getVolunteer(), $content),
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
            $this->get(LoggerInterface::class)->warning('Cannot retrieve answer\'s sentiment', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }
}