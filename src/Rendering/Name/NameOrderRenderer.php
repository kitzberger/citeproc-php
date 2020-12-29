<?php
declare(strict_types=1);
/*
 * citeproc-php: NameOrderRenderer.php
 * User: Sebastian Böttger <sebastian.boettger@galeria-reisen.de>
 * created at 29.12.20, 12:25
 */

namespace Seboettg\CiteProc\Rendering\Name;

use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Style\Options\DemoteNonDroppingParticle;
use Seboettg\CiteProc\Style\Options\GlobalOptions;
use Seboettg\CiteProc\Style\Options\NameOptions;
use Seboettg\CiteProc\Util\NameHelper;
use Seboettg\CiteProc\Util\StringHelper;
use stdClass;

class NameOrderRenderer
{
    /** @var GlobalOptions */
    private $globalOptions;

    /** @var NameOptions */
    private $nameOptions;

    /** @var NamePart[] */
    private $nameParts;

    /** @var string */
    private $delimiter;

    /**
     * NameOrderRenderer constructor.
     * @param GlobalOptions $globalOptions
     * @param NamePart[] $nameParts
     * @param string $delimiter
     */
    public function __construct(
        GlobalOptions $globalOptions,
        array $nameParts,
        string $delimiter
    ) {
        $this->globalOptions = $globalOptions;
        $this->nameParts = $nameParts;
        $this->delimiter = $delimiter;
    }

    /**
     * @param stdClass $data
     * @param integer  $rank
     *
     * @return string
     * @throws CiteProcException
     */
    public function render(stdClass $data, int $rank): string
    {
        $nameAsSortOrder = (($this->nameOptions->getNameAsSortOrder() === "first" && $rank === 0) ||
            $this->nameOptions->getNameAsSortOrder() === "all");
        $demoteNonDroppingParticle = $this->globalOptions->getDemoteNonDroppingParticles();
        $normalizedName = NameHelper::normalizeName($data);
        $delimiter = $this->nameOptions->getNameDelimiter() ?? $this->delimiter;
        if (StringHelper::isLatinString($normalizedName) || StringHelper::isCyrillicString($normalizedName)) {
            if ($this->nameOptions->getForm() === "long"
                && $nameAsSortOrder
                && ((string) $demoteNonDroppingParticle === DemoteNonDroppingParticle::NEVER
                    || (string) $demoteNonDroppingParticle === DemoteNonDroppingParticle::SORT_ONLY)
            ) {
                // [La] [Fontaine], [Jean] [de], [III]
                NameHelper::prependParticleTo($data, "family", "non-dropping-particle");
                NameHelper::appendParticleTo($data, "given", "dropping-particle");

                list($family, $given) = $this->renderNameParts($data);

                $text = $family . (!empty($given) ? $this->nameOptions->getSortSeparator() . $given : "");
                $text .= !empty($data->suffix) ? $this->nameOptions->getSortSeparator() . $data->suffix : "";
            } elseif ($this->nameOptions->getForm() === "long"
                && $nameAsSortOrder
                && (is_null($demoteNonDroppingParticle)
                    || (string) $demoteNonDroppingParticle === DemoteNonDroppingParticle::DISPLAY_AND_SORT)
            ) {
                // [Fontaine], [Jean] [de] [La], [III]
                NameHelper::appendParticleTo($data, "given", "dropping-particle");
                NameHelper::appendParticleTo($data, "given", "non-dropping-particle");
                list($family, $given) = $this->renderNameParts($data);
                $text = $family;
                $text .= !empty($given) ? $this->nameOptions->getSortSeparator() . $given : "";
                $text .= !empty($data->suffix) ? $this->nameOptions->getSortSeparator() . $data->suffix : "";
            } elseif ($this->nameOptions->getForm() === "long" && $nameAsSortOrder
                && empty($demoteNonDroppingParticle)) {
                list($family, $given) = $this->renderNameParts($data);
                $text = $family;
                $text .= !empty($given) ? $delimiter . $given : "";
                $text .= !empty($data->suffix) ? $this->nameOptions->getSortSeparator() . $data->suffix : "";
            } elseif ($this->nameOptions->getForm() === "short") {
                // [La] [Fontaine]
                NameHelper::prependParticleTo($data, "family", "non-dropping-particle");
                $text = $data->family;
            } else {// form "long" (default)
                // [Jean] [de] [La] [Fontaine] [III]
                NameHelper::prependParticleTo($data, "family", "non-dropping-particle");
                NameHelper::prependParticleTo($data, "family", "dropping-particle");
                NameHelper::appendParticleTo($data, "family", "suffix");
                list($family, $given) = $this->renderNameParts($data);
                $text = !empty($given) ? $given . " " . $family : $family;
            }
        } elseif (StringHelper::isAsianString(NameHelper::normalizeName($data))) {
            $text = $this->nameOptions->getForm() === "long" ? $data->family . $data->given : $data->family;
        } else {
            $text = $this->nameOptions->getForm() === "long" ? $data->family . " " . $data->given : $data->family;
        }
        return $text;
    }


    /**
     * @param  $data
     * @return array
     */
    private function renderNameParts($data): array
    {
        $given = "";
        if (array_key_exists("family", $this->nameParts)) {
            $family = $this->nameParts["family"]->render($data);
        } else {
            $family = $data->family;
        }
        if (isset($data->given)) {
            if (array_key_exists("given", $this->nameParts)) {
                $given = $this->nameParts["given"]->render($data);
            } else {
                $given = $data->given;
            }
        }
        return [$family, $given];
    }

    /**
     * @param NameOptions $nameOptions
     */
    public function setNameOptions(NameOptions $nameOptions): void
    {
        $this->nameOptions = $nameOptions;
    }
}
