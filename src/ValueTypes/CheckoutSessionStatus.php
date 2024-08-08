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

/**
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-status
 */
enum CheckoutSessionStatus: string
{
    case Complete = "complete";
    case Expired = "expired";
    case Open = "open";

    /**
     * @throws Exception
     */
    public static function fromString(string $status): self
    {
        return match( $status ){
          "complete" => self::Complete,
          "expired" => self::Expired,
          "open" => self::Open,
          default => throw new Exception("Invalid SessionStatus '{$status}'"),
        };
    }
}