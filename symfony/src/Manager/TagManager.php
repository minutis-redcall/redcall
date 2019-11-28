<?php

namespace App\Manager;

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
    public function findAll() : array
    {
        return $this->tagRepository->findAll();
    }
}