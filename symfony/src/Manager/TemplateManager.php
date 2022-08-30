<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\Template;
use App\Enum\Type;
use App\Repository\TemplateRepository;
use App\Security\Helper\Security;
use Doctrine\ORM\QueryBuilder;

class TemplateManager
{
    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * @var Security
     */
    private $security;

    public function __construct(TemplateRepository $templateRepository, Security $security)
    {
        $this->templateRepository = $templateRepository;
        $this->security           = $security;
    }

    public function getTemplatesForStructure(Structure $structure) : QueryBuilder
    {
        return $this->templateRepository->getTemplatesForStructure($structure);
    }

    public function find(int $id) : ?Template
    {
        return $this->templateRepository->find($id);
    }

    /**
     * @return Template[]
     */
    public function findByTypeForCurrentUser(Type $type) : array
    {
        return $this->templateRepository->findByTypeForUserStructures(
            $this->security->getUser(),
            $type
        );
    }

    public function add(Template $template)
    {
        $this->templateRepository->add($template);
    }

    public function remove(Template $template)
    {
        $this->templateRepository->remove($template);
    }
}