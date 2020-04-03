<?php

namespace App\Structure;

use Symfony\Component\Validator\Constraints as Assert;
use App\Structure\Validation\Constraints\ParentStructure;

/**
 * @ParentStructure
 */
class StructureImportModel
{
    /** @var int|null */
    private $id;

    /** @var int */
    private $importId;

    /** @var string */
    private $identifier;

    /**
     * @var string
     * @Assert\Length(min = 1, max = 16)
     */
    private $type;

    /**
     * @var string
     * @Assert\Length(min = 1, max = 255)
     */
    private $name;

    /** @var string|null s*/
    private $parentStructure;

    /** @var string */
    private $enabled;

    /**
     * @var string
     * @Assert\Length(min = 1, max = 255)
     */
    private $president;

    /** @var bool */
    private $imported = false;

    /**
     * StructureImportModel constructor.
     *
     * @param string      $identifier
     * @param string      $type
     * @param string      $name
     * @param string|null $parentStructure
     * @param string      $enabled
     * @param string      $president
     * @param bool        $imported
     * @param int         $id
     */
    public function __construct(
        string $identifier,
        string $type,
        string $name,
        ?string $parentStructure,
        string $enabled,
        string $president,
        bool $imported = false,
        int $id = null
    ) {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->name = $name;
        $this->parentStructure = $parentStructure;
        $this->enabled = $enabled;
        $this->president = $president;
        $this->imported = $imported;
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getImportId(): int
    {
        return $this->importId;
    }

    /**
     * @param int $importId
     */
    public function setImportId(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getParentStructure(): ?string
    {
        return $this->parentStructure;
    }

    /**
     * @return string
     */
    public function getEnabled(): string
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getPresident(): string
    {
        return $this->president;
    }

    /**
     * @return bool
     */
    public function isImported(): bool
    {
        return $this->imported;
    }

    /**
     * @param bool $imported
     */
    public function setImported(bool $imported): void
    {
        $this->imported = $imported;
    }
}
