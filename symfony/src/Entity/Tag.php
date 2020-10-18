<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 */
class Tag
{
    const TAG_SOCIAL_ASSISTANCE    = 'social_assistance';
    const TAG_EMERGENCY_ASSISTANCE = 'emergency_assistance';
    const TAG_PSC_1                = 'psc_1';
    const TAG_PSE_1                = 'pse_1';
    const TAG_PSE_2                = 'pse_2';
    const TAG_CI                   = 'ci';
    const TAG_DRVR_VL              = 'drvr_vl';
    const TAG_DRVR_VPSP            = 'drvr_vpsp';
    const TAG_TCAU                 = 'tcau'; // Tronc Commun Acteurs Urgence
    const TAG_TCEO                 = 'tceo'; // Tronc Commun Encadrants Operationnels
    const TAG_DLAS                 = 'dlas'; // Directeur Local Action Social
    const TAG_DLUS                 = 'dlus'; // Directeur Local Urgence Secours
    const TAG_CEM                  = 'cem';  // Team lead
    const TAG_MAR                  = 'mar';  // Maraudeur

    const TAGS = [
        self::TAG_SOCIAL_ASSISTANCE,
        self::TAG_EMERGENCY_ASSISTANCE,
        self::TAG_PSC_1,
        self::TAG_PSE_1,
        self::TAG_PSE_2,
        self::TAG_CI,
        self::TAG_DRVR_VL,
        self::TAG_DRVR_VPSP,
        self::TAG_TCAU,
        self::TAG_TCEO,
        self::TAG_DLAS,
        self::TAG_DLUS,
        self::TAG_MAR,
        self::TAG_CEM,
    ];

    const PRIORITY = [
        self::TAG_SOCIAL_ASSISTANCE,
        self::TAG_EMERGENCY_ASSISTANCE,
        self::TAG_PSC_1,
        self::TAG_PSE_1,
        self::TAG_PSE_2,
        self::TAG_CI,
        self::TAG_MAR,
        self::TAG_CEM,
        self::TAG_TCAU,
        self::TAG_TCEO,
        self::TAG_DLAS,
        self::TAG_DLUS,
        self::TAG_DRVR_VL,
        self::TAG_DRVR_VPSP,
    ];

    const HIERARCHY = [
        self::TAG_CI    => [self::TAG_PSE_2],
        self::TAG_PSE_2 => [self::TAG_PSE_1],
        self::TAG_PSE_1 => [self::TAG_PSC_1],

        self::TAG_DRVR_VPSP => [self::TAG_DRVR_VL],

        self::TAG_CEM => [self::TAG_MAR],
    ];

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=200)
     */
    private $label;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Volunteer", mappedBy="tags")
     */
    private $volunteers;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
    }

    /**
     * This method builds a map of tags based on the tag hierarchy.
     *
     * It creates, for each node of the tree, an array of the tags that
     * are below in the hierarchy in order to remove them easily in the views.
     *
     * @return array
     */
    static public function getTagHierarchyMap()
    {
        static $map = [];

        if (count($map)) {
            return $map;
        }

        foreach (self::HIERARCHY as $main => $tags) {
            $map[$main]     = $tags;
            $visited        = [];
            $additionalTags = $tags;
            while ($tag = array_shift($additionalTags)) {
                if (!isset(self::HIERARCHY[$tag])) {
                    continue;
                }

                $visited[] = $tag;

                foreach (self::HIERARCHY[$tag] as $tagToAdd) {
                    $map[$main][] = $tagToAdd;
                }

                foreach (array_diff(self::HIERARCHY[$tag], $visited) as $additionalTag) {
                    $additionalTags[] = $additionalTag;
                }
            }

            $map[$main] = array_unique($map[$main]);
        }

        return $map;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getVolunteers()
    {
        return $this->volunteers;
    }

    /**
     * @return int
     */
    public function getTagPriority(): int
    {
        $priority = array_search($this->label, self::PRIORITY);

        if (false === $priority) {
            return -1;
        }

        return $priority;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->label;
    }
}
