<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Component\HttpFoundation\EventStreamResponse;
use App\Entity\GeoLocation;
use App\Entity\Message;
use App\Form\Type\GeoLocationType;
use App\Repository\GeoLocationRepository;
use App\Repository\MessageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class GeoLocationController
 *
 * WARNING: this controller is OUT of the security firewall.
 *
 * @Route(path="geo/", name="geo_")
 */
class GeoLocationController extends BaseController
{
    /**
     * @var MessageRepository
     */
    protected $messageRepository;

    /**
     * @var GeoLocationRepository
     */
    protected $geoLocationRepository;

    /**
     * GeoLocationController constructor.
     *
     * @param MessageRepository     $messageRepository
     * @param GeoLocationRepository $geoLocationRepository
     */
    public function __construct(MessageRepository $messageRepository, GeoLocationRepository $geoLocationRepository)
    {
        $this->messageRepository     = $messageRepository;
        $this->geoLocationRepository = $geoLocationRepository;
    }

    /**
     * @Route(path="{code}", name="open")
     * @Method({"GET"})
     *
     * @param string $code
     *
     * @return Response
     */
    public function openAction(string $code)
    {
        /* @var Message $message */
        $message = $this->getMessageByWebCode($code);

        return $this->render('geo_location.html.twig', [
            'code'    => $code,
            'message' => $message,
        ]);
    }

    public function content(string $code)
    {
        /* @var Message $message */
        $message       = $this->getMessageByWebCode($code);
        $communication = $message->getCommunication();

        return $this->render('geo_location_content.html.twig', [
            'code'          => $code,
            'communication' => $communication,
            'message'       => $message,
            'form'          => $this->createForm(GeoLocationType::class)->createView(),
            'api_key'       => getenv('MAPBOX_API_KEY'),
            'status'        => $this->geoLocationRepository->getLastGeoLocationUpdateTimestamp($communication),
        ]);
    }

    /**
     * @Route(path="{code}/update", name="update")
     * @Method({"POST"})
     *
     * @param string $code
     *
     * @return Response
     */
    public function updateAction(Request $request, string $code)
    {
        /* @var Message $message */
        $message = $this->getMessageByWebCode($code);

        $geolocation = new GeoLocation();

        $form = $this
            ->createForm(GeoLocationType::class, $geolocation)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($message->getGeoLocation()) {
                $message->getGeoLocation()->setLatitude($geolocation->getLatitude());
                $message->getGeoLocation()->setLongitude($geolocation->getLongitude());
                $message->getGeoLocation()->setAccuracy($geolocation->getAccuracy());
                $message->getGeoLocation()->setHeading($geolocation->getHeading());
                $geolocation = $message->getGeoLocation();
            }

            $message->setGeoLocation($geolocation);
            $geolocation->setMessage($message);
            $geolocation->setDatetime(new \DateTime());

            $this->getManager()->persist($message);
            $this->getManager()->persist($geolocation);
            $this->getManager()->flush();
        }

        return new Response();
    }

    /**
     * @Route(path="{code}/poll", name="poll")
     *
     * @param string $code
     *
     * @return Response
     */
    public function pollAction(string $code)
    {
        /* @var Message $message */
        $message = $this->getMessageByWebCode($code);

        return new JsonResponse(
            $this->getGeolocationInformation($message)
        );
    }

    /**
     * @Route(path="{code}/sse/{status}", name="sse", requirements={"status" = "\d*"})
     *
     * @param string $code
     *
     * @return Response
     */
    public function sseAction(string $code, ?int $status = null)
    {
        /* @var Message $message */
        $message       = $this->getMessageByWebCode($code);
        $communication = $message->getCommunication();

        $prevUpdate = $status;
        $response   = new EventStreamResponse(function () use ($communication, $message, &$prevUpdate) {
            $newUpdate = $this->geoLocationRepository->getLastGeoLocationUpdateTimestamp($communication);

            if ($newUpdate !== $prevUpdate) {
                $prevUpdate = $newUpdate;
                $message    = $this->messageRepository->refresh($message);

                return json_encode($this->getGeolocationInformation($message));
            }
        });

        return $response;
    }

    /**
     * @param Message $message
     *
     * @return array
     */
    private function getGeolocationInformation(Message $myMessage): array
    {
        $data = [];
        foreach ($myMessage->getCommunication()->getMessages() as $message) {
            if ($geo = $message->getGeoLocation()) {
                $data[$message->getId()] = [
                    'latitude'  => $geo->getLatitude(),
                    'longitude' => $geo->getLongitude(),
                    'accuracy'  => $geo->getAccuracy(),
                    'heading'   => $geo->getHeading(),
                    'last_data' => $this->trans('geolocation.last_data', [
                        '%time%'     => $geo->getDatetime()->format('H:i'),
                        '%accuracy%' => $geo->getReadableAccuracy(),
                    ]),
                    'distance'  => $myMessage->getGeoLocation() ? $geo->getDistance($myMessage->getGeoLocation()) : null,
                ];
            }
        }

        return $data;
    }

    /**
     * @param string $code
     *
     * @return Message
     *
     * @throws NotFoundHttpException
     */
    private function getMessageByWebCode(string $code): Message
    {
        $message = $this->messageRepository->findOneBy([
            'webCode' => $code,
        ]);

        if (null === $message) {
            throw $this->createNotFoundException();
        }

        return $message;
    }
}