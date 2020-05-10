<?php

namespace Bundles\PegassCrawlerBundle\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Throwable;

/**
 * @ORM\Entity(repositoryClass="Bundles\PegassCrawlerBundle\Repository\PegassRepository")
 * @ORM\Table(
 * uniqueConstraints={
 *     @ORM\UniqueConstraint(name="type_identifier_idx", columns={"type", "identifier"})
 * },
 * indexes={
 *    @ORM\Index(name="type_update_idx", columns={"type", "updated_at"}),
 *    @ORM\Index(name="typ_ide_par_idx", columns={"type", "identifier", "parent_identifier"}),
 *    @ORM\Index(name="enabled_idx", columns={"enabled"})
 * })
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Pegass
{
    const TYPE_AREA       = 'area';
    const TYPE_DEPARTMENT = 'department';
    const TYPE_STRUCTURE  = 'structure';
    const TYPE_VOLUNTEER  = 'volunteer';

    const TYPES = [
        self::TYPE_AREA,
        self::TYPE_DEPARTMENT,
        self::TYPE_STRUCTURE,
        self::TYPE_VOLUNTEER,
    ];

    const TTL = [
        self::TYPE_AREA       => 365 * 24 * 60 * 60, // 1 year
        self::TYPE_DEPARTMENT => 90 * 24 * 60 * 60, // 3 months
        self::TYPE_STRUCTURE  => 7 * 24 * 60 * 60, // 1 week
        self::TYPE_VOLUNTEER  => 30 * 24 * 60 * 60, // 1 month
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $identifier;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $parentIdentifier;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $type;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getParentIdentifier(): ?string
    {
        return $this->parentIdentifier;
    }

    public function setParentIdentifier(?string $parentIdentifier): self
    {
        $this->parentIdentifier = $parentIdentifier;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @param string $expression
     *
     * @return array|string|null
     */
    public function evaluate(string $expression)
    {
        $content = $this->getContent();

        if (!$content) {
            return null;
        }

        try {
            $object = json_decode(json_encode($content));

            $accessed = PropertyAccess::createPropertyAccessorBuilder()
                                      ->disableExceptionOnInvalidPropertyPath()
                                      ->getPropertyAccessor()
                                      ->getValue($object, $expression);

            return json_decode(json_encode($accessed), true);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getXml() : string
    {
        $xml = sprintf('<%s>%s</%s>', $this->type, $this->toXml($this->getContent()), $this->type);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        return $dom->saveXML();
    }

    public function xpath(string $template, array $parameters = [])
    {
        // Covers DOMDocument memory leaks
        static $dom;
        static $xpath;

        // Builds the expression
        foreach ($parameters as $index => $parameter) {
            $template = str_replace(sprintf('{%d}', $index), $this->xpathQuote($parameter), $template);
        }

        $xml = sprintf('<%s>%s</%s>', $this->type, $this->toXml($this->getContent()), $this->type);

        if (!$dom) {
            $dom = new \DOMDocument();
        }
        $dom->loadXML($xml);

        if (!$xpath) {
            $xpath = new \DOMXPath($dom);
        }

        $xpath = new \DOMXPath($dom);
        $values = [];
        foreach ($xpath->query($template) as $match) {
            /** @var \DOMElement $match */
            $values[] = [$match->nodeName => $match->nodeValue];
        }

        return $values;
    }

    /**
     * Credits:
     * https://stackoverflow.com/a/1352556/1067003
     *
     * @param string $value
     *
     * @return string
     */
    public function xpathQuote(string $value) : string
    {
        if (false === strpos($value, '"')) {
            return '"' . $value . '"';
        }
        if (false === strpos($value, '\'')) {
            return '\'' . $value . '\'';
        }

        // if the value contains both single and double quotes, construct an
        // expression that concatenates all non-double-quote substrings with
        // the quotes, e.g.:
        //
        // concat("'foo'", '"', "bar")
        $sb = 'concat(';
        $substrings = explode('"', $value);
        for($i = 0; $i < count($substrings); ++ $i) {
            $needComma = ($i > 0);
            if ($substrings [$i] !== '') {
                if ($i > 0) {
                    $sb .= ', ';
                }
                $sb .= '"' . $substrings [$i] . '"';
                $needComma = true;
            }
            if ($i < (count($substrings) - 1)) {
                if ($needComma) {
                    $sb .= ', ';
                }
                $sb .= "'\"'";
            }
        }
        $sb .= ')';

        return $sb;
    }

    /**
     * Credits:
     * https://stackoverflow.com/a/45905136/731138
     *
     * @param array  $arr
     * @param string $name_for_numeric_keys
     * @param int    $nest
     *
     * @return string
     */
    private function toXml(array $arr, string $name_for_numeric_keys = 'val', int $nest = 0): string
    {
        // Covers DOMDocument memory leaks
        static $tmpDom = [];

        if (empty($arr)) {
            // avoid having a special case for <root/> and <root></root> i guess
            return '';
        }

        $is_iterable_compat = function($v): bool {
            // php 7.0 compat for php7.1+'s is_itrable
            return is_array($v) ||($v instanceof \Traversable);
        };

        $isAssoc = function(array $arr): bool {
            // thanks to Mark Amery for this
            if (array() === $arr)
                return false;

            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        $endsWith = function(string $haystack, string $needle): bool {
            // thanks to MrHus
            $length = strlen($needle);
            if ($length == 0) {
                return true;
            }

            return (substr($haystack, - $length) === $needle);
        };

        // $arr = new RecursiveArrayIterator ( $arr );
        // $iterator = new RecursiveIteratorIterator ( $arr, RecursiveIteratorIterator::SELF_FIRST );
        $iterator = $arr;
        $domd = new \DOMDocument();
        $root = $domd->createElement('root');
        foreach ($iterator as $key => $val) {
            $ele = $domd->createElement(is_int($key) ? $name_for_numeric_keys : $key);
            if (!empty($val) || $val === '0') {
                if ($is_iterable_compat($val)) {
                    $asoc = $isAssoc($val);
                    $tmp = $this->toXml($val, is_int($key) ? $name_for_numeric_keys : $key, $nest + 1);
                    if (!($tmpDom[$nest] ?? false)) {
                        $tmpDom[$nest] = new \DOMDocument();
                    }
                    @$tmpDom[$nest]->loadXML('<root>' . $tmp . '</root>');
                    foreach ($tmpDom[$nest]->getElementsByTagName("root")->item(0)->childNodes ?? [] as $tmp2) {
                        $tmp3 = $domd->importNode($tmp2, true);
                        if ($asoc) {
                            $ele->appendChild($tmp3);
                        } else {
                            $root->appendChild($tmp3);
                        }
                    }
                    unset($tmp, $tmp2, $tmp3);
                    if (!$asoc) {
                        continue;
                    }
                } else {
                    $ele->textContent = $val;
                }
            }
            $root->appendChild($ele);
            unset($ele);
        }

        $domd->preserveWhiteSpace = false;
        $domd->formatOutput = true;
        $ret = trim($domd->saveXML($root));
        $ret = trim(substr($ret, strlen('<root>'), -strlen('</root>')));

        return $ret;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(?array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function __toString()
    {
        return sprintf('%s[%s/%s]', $this->type, $this->identifier, $this->parentIdentifier);
    }
}
