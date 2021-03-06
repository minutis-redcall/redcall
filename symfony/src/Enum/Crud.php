<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this CREATE
 * @method static $this READ
 * @method static $this UPDATE
 * @method static $this DELETE
 */
final class Crud extends Enum
{
    private const CREATE = 'CREATE';
    private const READ   = 'READ';
    private const UPDATE = 'UPDATE';
    private const DELETE = 'DELETE';
}