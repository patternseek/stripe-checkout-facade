<?php
/**
 *
 * © 2024 Tolan Blundell.  All rights reserved.
 * <tolan@patternseek.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
declare(strict_types=1);

namespace PatternSeek\StripeCheckoutFacade\ValueTypes;

use Exception;

enum CheckoutLocale: string
{
    case auto = "auto";
    case bg = "bg";
    case cs = "cs";
    case da = "da";
    case de = "de";
    case el = "el";
    case en = "en";
    case enGB = "en-GB";
    case es = "es";
    case es419 = "es-419";
    case et = "et";
    case fi = "fi";
    case fil = "fil";
    case fr = "fr";
    case frCA = "fr-CA";
    case hr = "hr";
    case hu = "hu";
    case id = "id";
    case it = "it";
    case ja = "ja";
    case ko = "ko";
    case lt = "lt";
    case lv = "lv";
    case ms = "ms";
    case mt = "mt";
    case nb = "nb";
    case nl = "nl";
    case pl = "pl";
    case ptBR = "pt-BR";
    case pt = "pt";
    case ro = "ro";
    case ru = "ru";
    case sk = "sk";
    case sl = "sl";
    case sv = "sv";
    case th = "th";
    case tr = "tr";
    case vi = "vi";
    case zh = "zh";
    case zhHK = "zh-HK";
    case zhTW = "zh-TW";

    /**
     * @param string $str
     * @return CheckoutLocale
     * @throws Exception
     */
    public static function fromStringAutoIfNoMatch( string $str ): CheckoutLocale
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return self::matchString($str, false);
    }

    /**
     * @param string $str
     * @return CheckoutLocale
     * @throws Exception
     */
    public static function fromStringExceptionIfNoMatch( string $str ): CheckoutLocale
    {
        return self::matchString($str, true);
    }

    /**
     * @param string $localeString
     * @param bool $errorOnNoMatch
     * @return CheckoutLocale
     * @throws Exception
     */
    private static function matchString(string $localeString, bool $errorOnNoMatch ): CheckoutLocale
    {
        // These are the support
        return match ($localeString) {
            default => $errorOnNoMatch?throw new Exception("Unknown locale for Checkout."):self::auto,
            "bg" => self::bg,
            "cs" => self::cs,
            "da" => self::da,
            "de" => self::de,
            "el" => self::el,
            "en" => self::en,
            "en-GB" => self::enGB,
            "es" => self::es,
            "es-419" => self::es419,
            "et" => self::et,
            "fi" => self::fi,
            "fil" => self::fil,
            "fr" => self::fr,
            "fr-CA" => self::frCA,
            "hr" => self::hr,
            "hu" => self::hu,
            "id" => self::id,
            "it" => self::it,
            "ja" => self::ja,
            "ko" => self::ko,
            "lt" => self::lt,
            "lv" => self::lv,
            "ms" => self::ms,
            "mt" => self::mt,
            "nb" => self::nb,
            "nl" => self::nl,
            "pl" => self::pl,
            "pt-BR" => self::ptBR,
            "pt" => self::pt,
            "ro" => self::ro,
            "ru" => self::ru,
            "sk" => self::sk,
            "sl" => self::sl,
            "sv" => self::sv,
            "th" => self::th,
            "tr" => self::tr,
            "vi" => self::vi,
            "zh" => self::zh,
            "zh-HK" => self::zhHK,
            "zh-TW" => self::zhTW,
        };
    }
}