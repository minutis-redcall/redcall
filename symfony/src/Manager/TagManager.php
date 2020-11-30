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

    public function find(int $tagId) : ?Tag
    {
        return $this->tagRepository->find($tagId);
    }

    /**
     * @return array
     */
    public function findAll() : array
    {
        return $this->tagRepository->findAll();
    }

    /**
     * @param string $label
     *
     * @return Tag|null
     */
    public function findOneByLabel(string $label) : ?Tag
    {
        return $this->tagRepository->findOneByLabel($label);
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

    public function findTagsForNivols(array $nivols) : array
    {
        return $this->tagRepository->findTagsByNivol($nivols);
    }
}