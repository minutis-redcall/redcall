<?php

namespace App\Manager;

use App\Entity\Tag;
use App\Repository\TagRepository;

class TagManager
{
    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @param TagRepository $tagRepository
     */
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->tagRepository->findAll();
    }

    /**
     * @param string $label
     *
     * @return Tag|null
     */
    public function findOneByLabel(string $label): ?Tag
    {
        static $cache = [];

        if (array_key_exists($label, $cache)) {
            return $cache[$label];
        }

        $cache[$label] = $this->tagRepository->findOneByLabel($label);

        return $cache[$label];
    }

    /**
     * @param Tag $tag
     */
    public function create(Tag $tag)
    {
        if (!$this->tagRepository->findByLabel($tag->getLabel())) {
            $this->tagRepository->save($tag);
        }
    }
}