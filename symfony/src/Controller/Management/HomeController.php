<?php

namespace App\Controller\Management;

use App\Base\BaseController;
use App\Entity\Structure;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: "management/", name: "management_")]
class HomeController extends BaseController
{
    #[Route(name: "home")]
    public function indexAction()
    {
        $user = $this->getUser();

        $structures = $user->getStructures()->toArray();
        usort($structures, static function (Structure $a, Structure $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $byId = [];
        foreach ($structures as $structure) {
            $byId[$structure->getId()] = $structure;
        }

        $roots            = [];
        $childrenByParent = [];
        foreach ($structures as $structure) {
            $parent = $structure->getParentStructure();
            if ($parent && isset($byId[$parent->getId()])) {
                $childrenByParent[$parent->getId()][] = $structure;
                continue;
            }
            $roots[] = $structure;
        }

        $peerIds = [];
        foreach ($structures as $structure) {
            foreach ($structure->getUsers() as $peer) {
                if ($peer->getId() !== $user->getId()) {
                    $peerIds[$peer->getId()] = true;
                }
            }
        }

        return $this->render('management/home.html.twig', [
            'email'             => getenv('MINUTIS_SUPPORT'),
            'roots'             => $roots,
            'childrenByParent'  => $childrenByParent,
            'structuresCount'   => count($structures),
            'peersCount'        => count($peerIds),
        ]);
    }
}
