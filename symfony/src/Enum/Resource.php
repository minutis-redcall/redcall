<?php

namespace App\Enum;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use MyCLabs\Enum\Enum;

/**
 * @method static $this CATEGORY
 * @method static $this BADGE
 * @method static $this USER
 * @method static $this STRUCTURE
 * @method static $this VOLUNTEER
 * @method static $this PHONE
 */
class Resource extends Enum
{
    private const CATEGORY  = Category::class;
    private const BADGE     = Badge::class;
    private const USER      = User::class;
    private const STRUCTURE = Structure::class;
    private const VOLUNTEER = Volunteer::class;

    public function getManager() : string
    {
        switch ($this->value) {
            case self::CATEGORY:
                return CategoryManager::class;
            case self::BADGE:
                return BadgeManager::class;
            case self::USER:
                return UserManager::class;
            case self::STRUCTURE:
                return StructureManager::class;
            case self::VOLUNTEER:
                return VolunteerManager::class;
        }
    }

    public function getVoter() : string
    {
        switch ($this->value) {
            case self::CATEGORY:
                return 'CATEGORY';
            case self::BADGE:
                return 'BADGE';
            case self::USER:
                return 'USER';
            case self::STRUCTURE:
                return 'STRUCTURE';
            case self::VOLUNTEER:
                return 'VOLUNTEER';
        }
    }

    public function getProviderMethod()
    {
        switch ($this->value) {
            case self::USER:
                return 'findOneByUsernameAndPlatform';
            default:
                return 'findOneByExternalId';
        }
    }

    public function getPersisterMethod()
    {
        return 'save';
    }

    public function getDisplayName() : string
    {
        return strtolower($this->getVoter());
    }
}