<?php

namespace App\Controller;

use App\Base\BaseController;
use App\Entity\GeoLocation;
use App\Entity\Message;
use App\Form\Type\GeoLocationType;
use App\Manager\GeoLocationManager;
use App\Manager\MessageManager;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var GeoLocationManager
     */
    private $geoLocationManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param GeoLocationManager $geoLocationManager
     * @param MessageManager     $messageManager
     */
    public function __construct(GeoLocationManager $geoLocationManager, MessageManager $messageManager)
    {
        $this->geoLocationManager = $geoLocationManager;
        $this->messageManager     = $messageManager;
    }

    /**
     * @Route(path="{code}", name="open", methods={"GET"})
     */
    public function openAction(Message $message)
    {
        $communication = $message->getCommunication();

        return $this->render('geo_location/index.html.twig', [
            'code'          => $message->getCode(),
            'communication' => $communication,
            'message'       => $message,
            'form'          => $this->createForm(GeoLocationType::class)->createView(),
            'api_key'       => getenv('MAPBOX_API_KEY'),
            'status'        => $this->geoLocationManager->getLastGeoLocationUpdateTimestamp($communication),
        ]);
    }

    /**
     * @Route(path="{code}/update", name="update", methods={"POST"})
     */
    public function updateAction(Request $request, Message $message)
    {
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
            $geolocation->setDatetime(new DateTime());

            $this->messageManager->save($message);
            $this->geoLocationManager->save($geolocation);
        }

        return new Response();
    }

    /**
     * @Route(path="{code}/poll", name="poll")
     */
    public function pollAction(Message $message)
    {
        return new JsonResponse(
            $this->getGeolocationInformation($message)
        );
    }

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
}