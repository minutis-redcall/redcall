<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this CREATE
 * @method static $this READ
 * @method static $this UPDATE
 * @method static $this DELETE
 * @method static $this LOCK
 * @method static $this UNLOCK
 * @method static $this DISABLE
 * @method static $this ENABLE
 */
final class Crud extends Enum
{
    private const CREATE = 'CREATE';
    private const READ   = 'READ';
    private const UPDATE = 'UPDATE';
    private const DELETE = 'DELETE';

    private const LOCK    = 'LOCK';
    private const UNLOCK  = 'UNLOCK';
    private const DISABLE = 'DISABLE';
    private const ENABLE  = 'ENABLE';
}