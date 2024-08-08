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

/**
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-mode
 */
enum CheckoutMode: string
{
    case Payment = "payment";
    // https://docs.stripe.com/payments/checkout/how-checkout-works#mixed
    case SubscriptionOrMixed = "subscription";
    case Setup = "setup";
}