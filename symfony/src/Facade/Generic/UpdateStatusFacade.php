<?php

namespace App\Facade\Generic;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class UpdateStatusFacade implements FacadeInterface
{
    /**
     * Entry whose update was requested.
     *
     * @var string
     */
    private $entry;

    /**
     * Whether entry has been successfully updated.
     *
     * @var bool
     */
    private $success;

    /**
     * A message giving more context about a potential failure.
     *
     * @var string
     */
    private $context;

    public function __construct(string $entry, bool $success = true, string $context = 'OK', ...$_)
    {
        $this->entry   = $entry;
        $this->success = $success;
        $this->context = call_user_func_array('sprintf', array_merge([$context], $_));
    }

    static public function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        return new static('demo');
    }

    public function getEntry() : string
    {
        return $this->entry;
    }

    public function isSuccess() : bool
    {
        return $this->success;
    }

    public function getContext() : string
    {
        return $this->context;
    }
}