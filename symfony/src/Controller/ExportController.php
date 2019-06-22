<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Component\HttpFoundation\ArrayToCsvResponse;
use App\Component\HttpFoundation\MpdfResponse;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Tag;
use App\Entity\Volunteer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="export_", path="export/")
 */
class ExportController extends BaseController
{
    /**
     * @Route(path="{communicationId}/csv", name="csv", requirements={"communicationId" = "\d+"})
     * @Method("POST")
     *
     * @param Request $request
     * @param int $communicationId
     *
     * @return Response
     */
    public function csvAction(Request $request, int $communicationId)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $communication = $this->getCommunication($communicationId);

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

            $tags = implode(', ', array_map(function (Tag $tag) {
                return $this->trans(sprintf('tag.shortcuts.%s', $tag->getLabel()));
            }, $volunteer->getTags()->toArray()));

            $row = [
                $this->trans('csv_export.nivol') => $volunteer->getNivol(),
                $this->trans('csv_export.firstname') => $volunteer->getFirstName(),
                $this->trans('csv_export.lastname') => $volunteer->getLastName(),
                $this->trans('csv_export.email') => $volunteer->getEmail(),
                $this->trans('csv_export.phone_number') => $volunteer->getFormattedPhoneNumber(),
                $this->trans('csv_export.tags') => $tags,
                $this->trans('csv_export.sent') => $this->trans($message->isSent() ? 'base.yes' : 'base.no'),
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
     * @Route(path="{communicationId}/portrait-pdf", name="portrait_pdf", requirements={"communicationId" = "\d+"})
     * @Method("POST")
     *
     * @param Request $request
     * @param int $communicationId
     *
     * @return MpdfResponse
     *
     * @throws \Mpdf\MpdfException
     */
    public function portraitPdfAction(Request $request, int $communicationId)
    {
        $this->validateCsrfOrThrowNotFoundException('communication', $request->request->get('csrf'));

        $communication = $this->getCommunication($communicationId);
        $selection = $this->getSelection($request, $communication);
        $campaign = $communication->getCampaign();

        $tables = [];
        $messages = $communication->getMessages()->toArray();
        if ($communication->getChoices()->toArray()) {
            // Get one table per communication choice
            foreach ($communication->getChoices() as $choice) {
                $label = $choice->getLabel();
                $tables[$label] = [];
                foreach ($messages as $message) {
                    if (!in_array($message->getVolunteer()->getId(), $selection)) {
                        continue;
                    }

                    /* @var \App\Entity\Answer|null $answer */
                    if (!$communication->isMultipleAnswer()) {
                        $answer = $message->getLastAnswer();
                        if ($answer && $answer->getChoice()
                            && $answer->getChoice()->getId() == $choice->getId()) {
                            $tables[$label][] = [
                                'volunteer' => $message->getVolunteer(),
                                'answer' => $answer,
                            ];
                        }
                    } elseif ($message->getAnswers()) {
                        foreach ($message->getAnswers() as $answer) {
                            if ($answer->getChoice() && $answer->getChoice()->getId() == $choice->getId()) {
                                $tables[$label][] = [
                                    'volunteer' => $message->getVolunteer(),
                                    'answer' => $answer,
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
            usort($tables[$label], function(array $rowA, array $rowB) {
                /* @var Volunteer $volunteerA */
                $volunteerA = $rowA['volunteer'];
                /* @var Volunteer $volunteerB */
                $volunteerB = $rowB['volunteer'];

                return -1 * ($volunteerA->getTagPriority() <=> $volunteerB->getTagPriority());
            });
        }

        $context = [
            'tempDir' => sys_get_temp_dir(),
            'current_date' => new \DateTime(),
            'campaign' => $campaign,
            'communication' => $communication,
            'tables' => $tables,
        ];

        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 25,
        ]);

        $mpdf->SetHTMLHeader($this->renderView('export/portrait_pdf/header.html.twig', $context));
        $mpdf->SetHTMLFooter($this->renderView('export/portrait_pdf/footer.html.twig', $context));
        $mpdf->WriteHTML($this->renderView('export/portrait_pdf/body.html.twig', $context));

        return new MpdfResponse(
            $mpdf,
            sprintf('export-portrait-%s.pdf', date('Y-m-d'))
        );
    }

    /**
     * @param $communicationId
     *
     * @return Communication
     */
    private function getCommunication($communicationId): Communication
    {
        /* @var Communication $communication */
        $communication = $this->getManager(Communication::class)->find($communicationId);

        if (!$communication) {
            throw $this->createNotFoundException();
        }

        return $communication;
    }

    /**
     * @param Request $request
     * @param Communication $communication
     *
     * @return array
     */
    private function getSelection(Request $request, Communication $communication): array
    {
        $selection = json_decode($request->request->get('volunteers'), true);
        if (!$selection && $communication->getMessages()) {
            $selection = array_map(function (Message $message) {
                return $message->getVolunteer()->getId();
            }, $communication->getMessages()->toArray());
        }

        return $selection;
    }
}