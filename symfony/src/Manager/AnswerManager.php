<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Enum\Stop;
use App\Enum\Type;
use App\Repository\AnswerRepository;
use Doctrine\ORM\QueryBuilder;
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
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(AnswerRepository $answerRepository,
        TranslatorInterface $translator)
    {
        $this->answerRepository = $answerRepository;
        $this->translator       = $translator;
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
}