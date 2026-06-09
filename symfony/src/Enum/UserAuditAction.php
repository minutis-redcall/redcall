<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static $this CREATE
 * @method static $this UPDATE
 * @method static $this DELETE
 */
final class UserAuditAction extends Enum
{
    private const CREATE = 'create';
    private const UPDATE = 'update';
    private const DELETE = 'delete';
}
