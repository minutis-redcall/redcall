<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Enum\Stop;
use App\Enum\Type;
use App\Provider\SMS\SMSProvider;
use App\Repository\AnswerRepository;
use App\Services\MessageFormatter;
use App\Tools\PhoneNumber;
use Doctrine\ORM\QueryBuilder;
use Google\Cloud\Language\LanguageClient;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AnswerManager
{
    /**
     * @var AnswerRepository
     */
    private $answerRepository;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var SMSProvider
     */
    private $smsProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AnswerRepository $answerRepository,
        SMSProvider $smsProvider,
        TranslatorInterface $translator,
        MessageFormatter $formatter,
        LoggerInterface $logger)
    {
        $this->answerRepository = $answerRepository;
        $this->smsProvider      = $smsProvider;
        $this->translator       = $translator;
        $this->formatter        = $formatter;
        $this->logger           = $logger;
    }

    /**
     * @required
     *
     * @param VolunteerManager $volunteerManager
     */
    public function setVolunteerManager(VolunteerManager $volunteerManager)
    {
        $this->volunteerManager = $volunteerManager;
    }

    /**
     * @required
     *
     * @param CampaignManager $campaignManager
     */
    public function setCampaignManager(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    /**
     * @required
     *
     * @param UserManager $userManager
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @required
     *
     * @param MessageManager $messageManager
     */
    public function setMessageManager(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    public function getLastCampaignUpdateTimestamp(Campaign $campaign) : ?int
    {
        return $this->answerRepository->getLastCampaignUpdateTimestamp($campaign);
    }

    public function clearAnswers(Message $message)
    {
        $this->answerRepository->clearAnswers($message);
    }

    public function clearChoices(Message $message, array $choices)
    {
        $this->answerRepository->clearChoices($message, $choices);
    }

    public function save(Answer $answer)
    {
        $this->answerRepository->save($answer);
    }

    public function getSearchQueryBuilder(string $criteria) : QueryBuilder
    {
        return $this->answerRepository->getSearchQueryBuilder($criteria);
    }

    public function handleSpecialAnswers(string $phoneNumber, string $body)
    {
        if (Stop::isValid($body)) {
            $volunteer = $this->volunteerManager->findOneByPhoneNumber($phoneNumber);
            if (!$volunteer || !$volunteer->isPhoneNumberOptin()) {
                return;
            }

            $this->campaignManager->contact(
                $volunteer,
                Type::SMS(),
                $this->translator->trans('special_answers.title', [
                    '%keyword%' => $body,
                ]),
                $this->translator->trans('special_answers.stop')
            );

            $volunteer->setPhoneNumberOptin(false);

            $this->volunteerManager->save($volunteer);
        }
    }

    public function getVolunteerAnswersQueryBuilder(Volunteer $volunteer) : QueryBuilder
    {
        return $this->answerRepository->getVolunteerAnswersQueryBuilder($volunteer);
    }

    public function find(int $answerId) : ?Answer
    {
        return $this->answerRepository->find($answerId);
    }

    public function sendSms(Message $message, string $content)
    {
        $answer = new Answer();
        $answer->setMessage($message);
        $answer->setRaw($content);
        $answer->setReceivedAt(new \DateTime());
        $answer->setUnclear(true);
        $answer->setByAdmin($this->userManager->findForCurrentUser()->getUsername());

        $this->answerRepository->save($answer);

        $message->addAnswser($answer);
        $this->messageManager->save($message);

        $this->smsProvider->send(
            PhoneNumber::getSmsSender($message->getVolunteer()->getPhone()),
            $message->getVolunteer()->getPhoneNumber(),
            $this->formatter->formatSimpleSMSContent($content),
            ['message_id' => $message->getId()]
        );
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
            $this->logger->warning('Cannot retrieve answer\'s sentiment', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }
}