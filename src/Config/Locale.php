<?php
declare(strict_types=1);
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2020 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Config;

use MyCLabs\Enum\Enum;

/**
 *
 * @method static AF_ZA()
 * @method static AR()
 * @method static BG_BG()
 * @method static CA_AD()
 * @method static CY_GB()
 * @method static DA_DK()
 * @method static DE_AT()
 * @method static DE_CH()
 * @method static DE_DE()
 * @method static EL_GR()
 * @method static EN_GB()
 * @method static EN_US()
 * @method static ES_CL()
 * @method static ES_ES()
 * @method static ES_MX()
 * @method static ET_EE()
 * @method static EU()
 * @method static FA_IR()
 * @method static FI_FI()
 * @method static FR_CA()
 * @method static FR_FR()
 * @method static HE_IL()
 * @method static HU_HU()
 * @method static ID_ID()
 * @method static IS_IS()
 * @method static IT_IT()
 * @method static JA_JP()
 * @method static KM_KH()
 * @method static KO_KR()
 * @method static LA()
 * @method static LT_LT()
 * @method static LV_LV()
 * @method static MN_MN()
 * @method static NB_NO()
 * @method static NL_NL()
 * @method static NN_NO()
 * @method static PL_PL()
 * @method static PT_BR()
 * @method static PT_PT()
 * @method static RO_RO()
 * @method static RU_RU()
 * @method static SK_SK()
 * @method static SL_SL()
 * @method static SR_RS()
 * @method static SV_SE()
 * @method static TH_TH()
 * @method static TR_TR()
 * @method static UK_UA()
 * @method static VI_VN()
 * @method static ZH_CN()
 * @method static ZH_TW()
 */
class Locale extends Enum
{
    const AF_ZA = 'af-ZA';

    const AR = 'ar';

    const BG_BG = 'bg-BG';

    const CA_AD = 'ca-AD';

    const CY_GB = 'cy-GB';

    const DA_DK = 'da-DK';

    const DE_AT = 'de-AT';

    const DE_CH = 'de-CH';

    const DE_DE = 'de-DE';

    const EL_GR = 'el-GR';

    const EN_GB = 'en-GB';

    const EN_US = 'en-US';

    const ES_CL = 'es-CL';

    const ES_ES = 'es-ES';

    const ES_MX = 'es-MX';

    const ET_EE = 'et-EE';

    const EU = 'eu';

    const FA_IR = 'fa-IR';

    const FI_FI = 'fi-FI';

    const FR_CA = 'fr-CA';

    const FR_FR = 'fr-FR';

    const HE_IL = 'he-IL';

    const HU_HU = 'hu-HU';

    const ID_ID = 'id-ID';

    const IS_IS = 'is-IS';

    const IT_IT = 'it-IT';

    const JA_JP = 'ja-JP';

    const KM_KH = 'km-KH';

    const KO_KR = 'ko-KR';

    const LA = 'la';

    const LT_LT = 'lt-LT';

    const LV_LV = 'lv-LV';

    const MN_MN = 'mn-MN';

    const NB_NO = 'nb-NO';

    const NL_NL = 'nl-NL';

    const NN_NO = 'nn-NO';

    const PL_PL = 'pl-PL';

    const PT_BR = 'pt-BR';

    const PT_PT = 'pt-PT';

    const RO_RO = 'ro-RO';

    const RU_RU = 'ru-RU';

    const SK_SK = 'sk-SK';

    const SL_SL = 'sl-SL';

    const SR_RS = 'sr-RS';

    const SV_SE = 'sv-SE';

    const TH_TH = 'th-TH';

    const TR_TR = 'tr-TR';

    const UK_UA = 'uk-UA';

    const VI_VN = 'vi-VN';

    const ZH_CN = 'zh-CN';

    const ZH_TW = 'zh-TW';
}
