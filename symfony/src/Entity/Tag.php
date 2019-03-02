<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 */
class Tag
{
    // If you add or change tags, you should do it as well on:
    // - VolunteerImportCommand
    // - messages.xx.yml
    // - fixtures.sql
    const TAG_SOCIAL_ASSISTANCE    = 'social_assistance';
    const TAG_EMERGENCY_ASSISTANCE = 'emergency_assistance';
    const TAG_PSC_1                = 'psc_1';
    const TAG_PSE_1_I              = 'pse_1_i';
    const TAG_PSE_1_R              = 'pse_1_r';
    const TAG_PSE_2_I              = 'pse_2_i';
    const TAG_PSE_2_R              = 'pse_2_r';
    const TAG_CI_I                 = 'ci_i';
    const TAG_CI_R                 = 'ci_r';
    const TAG_DRVR_VL              = 'drvr_vl';
    const TAG_DRVR_VPSP            = 'drvr_vpsp';

    const TAGS = [
        self::TAG_SOCIAL_ASSISTANCE,
        self::TAG_EMERGENCY_ASSISTANCE,
        self::TAG_PSC_1,
        self::TAG_PSE_1_I,
        self::TAG_PSE_1_R,
        self::TAG_PSE_2_I,
        self::TAG_PSE_2_R,
        self::TAG_CI_I,
        self::TAG_CI_R,
        self::TAG_DRVR_VL,
        self::TAG_DRVR_VPSP,
    ];

    const PRIORITY = [
        self::TAG_SOCIAL_ASSISTANCE,
        self::TAG_EMERGENCY_ASSISTANCE,
        self::TAG_PSC_1,
        self::TAG_PSE_1_I,
        self::TAG_PSE_1_R,
        self::TAG_PSE_2_I,
        self::TAG_PSE_2_R,
        self::TAG_DRVR_VL,
        self::TAG_DRVR_VPSP,
        self::TAG_CI_I,
        self::TAG_CI_R,
    ];

    const HIERARCHY = [
        self::TAG_CI_R    => [self::TAG_CI_I],
        self::TAG_CI_I    => [self::TAG_PSE_2_R],
        self::TAG_PSE_2_R => [self::TAG_PSE_2_I],
        self::TAG_PSE_2_I => [self::TAG_PSE_1_R],
        self::TAG_PSE_1_R => [self::TAG_PSE_1_I],
        self::TAG_PSE_1_I => [self::TAG_PSC_1],

        self::TAG_DRVR_VPSP => [self::TAG_DRVR_VL],
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
    private $volunteers = [];

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
     * @return Volunteers[]
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
