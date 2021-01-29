<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Component\HttpFoundation\ArrayToCsvResponse;
use App\Component\HttpFoundation\MpdfResponse;
use App\Entity\Answer;
use App\Entity\Badge;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use DateTime;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(name="export_", path="export/")
 */
class ExportController extends BaseController
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route(path="{id}/csv", name="csv", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function csvAction(Request $request, Communication $communication)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $selection = json_decode($request->request->get('volunteers'), true);
        if (!$selection && $communication->getMessages()) {
            $selection = array_map(function (Message $message) {
                return $message->getVolunteer()->getId();
            }, $communication->getMessages()->toArray());
        }

        $rows = [];
        foreach ($communication->getMessages() as $message) {
            if (!in_array($message->getVolunteer()->getId(), $selection)) {
                continue;
            }

            /* @var Volunteer $volunteer */
            $volunteer = $message->getVolunteer();

            $tags = implode(', ', array_map(function (Badge $badge) {
                return $badge->getName();
            }, $volunteer->getVisibleBadges()));

            $row = [
                $this->trans('csv_export.nivol')        => $volunteer->getNivol(),
                $this->trans('csv_export.firstname')    => $volunteer->getFirstName(),
                $this->trans('csv_export.lastname')     => $volunteer->getLastName(),
                $this->trans('csv_export.email')        => $volunteer->getEmail(),
                $this->trans('csv_export.phone_number') => $volunteer->getFormattedPhoneNumber(),
                $this->trans('csv_export.tags')         => $tags,
                $this->trans('csv_export.sent')         => $this->trans($message->isSent() ? 'base.yes' : 'base.no'),
                $this->trans('csv_export.answer_time')  => $message->getLastAnswer() ? $message->getLastAnswer()->getReceivedAt()->format('Y-m-d H:i:s') : null,
            ];

            /* @var Choice $choice */
            foreach ($communication->getChoices() as $choice) {
                $answer = $message->getAnswerByChoice($choice);
                if (!$answer) {
                    $row[$this->trans('csv_export.choice', [
                        '%choice%' => $choice->getLabel(),
                    ])] = $this->trans('base.no');
                } else {
                    $row[$this->trans('csv_export.choice', [
                        '%choice%' => $choice->getLabel(),
                    ])] = sprintf('%s (%s)', $this->trans('base.yes'), $answer->getReceivedAt()->format('d/m/Y H:i'));
                }
            }

            $row[$this->trans('csv_export.other')] = $message->getInvalidAnswer() ? $message->getInvalidAnswer()->getRaw() : null;

            $rows[] = $row;
        }

        return new ArrayToCsvResponse($rows, sprintf('export-%s.csv', date('Y-m-d.H:i:s')));
    }

    /**
     * @Route(path="{id}/pdf", name="pdf", requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function pdfAction(Request $request, Communication $communication)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $selection = $this->getSelection($request, $communication);
        $campaign  = $communication->getCampaign();

        $tables   = [];
        $messages = $communication->getMessages()->toArray();
        if ($communication->getChoices()->toArray()) {
            // Get one table per communication choice
            foreach ($communication->getChoices() as $choice) {
                $label          = $choice->getLabel();
                $tables[$label] = [];
                foreach ($messages as $message) {
                    if (!in_array($message->getVolunteer()->getId(), $selection)) {
                        continue;
                    }

                    /* @var Answer|null $answer */
                    if (!$communication->isMultipleAnswer()) {
                        $answer = $message->getLastAnswer();
                        if ($answer && $answer->hasChoice($choice)) {
                            $tables[$label][] = [
                                'volunteer' => $message->getVolunteer(),
                                'answer'    => $answer,
                            ];
                        }
                    } elseif ($message->getAnswers()) {
                        foreach ($message->getAnswers() as $answer) {
                            if ($answer->hasChoice($choice)) {
                                $tables[$label][] = [
                                    'volunteer' => $message->getVolunteer(),
                                    'answer'    => $answer,
                                ];
                            }
                        }
                    }
                }
            }
        } else {
            // Get one table with all selected volunteers
            $tables[] = array_filter(array_map(function (Message $message) use ($selection) {
                if (!in_array($message->getVolunteer()->getId(), $selection)) {
                    return false;
                }

                return [
                    'volunteer' => $message->getVolunteer(),
                ];
            }, $messages));
        }
        foreach ($tables as $label => $table) {
            usort($tables[$label], function (array $rowA, array $rowB) {
                /* @var Volunteer $volunteerA */
                $volunteerA = $rowA['volunteer'];
                /* @var Volunteer $volunteerB */
                $volunteerB = $rowB['volunteer'];

                return ($volunteerA->getBadgePriority() <=> $volunteerB->getBadgePriority());
            });
        }

        $context = [
            'current_date'  => new DateTime(),
            'campaign'      => $campaign,
            'communication' => $communication,
            'tables'        => $tables,
        ];

        $mpdf = new Mpdf([
            'tempDir'       => sys_get_temp_dir(),
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_bottom' => 25,
        ]);

        $mpdf->SetHTMLHeader($this->renderView('export/pdf/header.html.twig', $context));
        $mpdf->SetHTMLFooter($this->renderView('export/pdf/footer.html.twig', $context));
        $mpdf->WriteHTML($this->renderView('export/pdf/body.html.twig', $context));

        return new MpdfResponse(
            $mpdf,
            sprintf('export-%s.pdf', date('Y-m-d'))
        );
    }

    /**
     * @param Request       $request
     * @param Communication $communication
     *
     * @return array
     */
    private function getSelection(Request $request, Communication $communication) : array
    {
        $selection = json_decode($request->get('volunteers'), true);
        if (!$selection && $communication->getMessages()) {
            $selection = array_map(function (Message $message) {
                return $message->getVolunteer()->getId();
            }, $communication->getMessages()->toArray());
        }

        return $selection;
    }

    private function trans($property, array $parameters = [])
    {
        return $this->translator->trans($property, $parameters);
    }
}