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
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-payment_status
 */
enum CheckoutSessionPaymentStatus: string
{
    case NoPaymentRequired = "no_payment_required";
    case Paid = "paid";
    case Unpaid = "unpaid";

    /**
     * @throws Exception
     */
    public static function fromString(string $status): self
    {
        return match( $status ){
          "no_payment_required" => self::NoPaymentRequired,
          "paid" => self::Paid,
          "unpaid" => self::Unpaid,
          default => throw new Exception("Invalid SessionPaymentStatus '{$status}'"),
        };
    }
}