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
 * https://docs.stripe.com/api/subscriptions/object#subscription_object-status
 */
enum SubscriptionStatus: string
{
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'canceled';
    case Unpaid = 'unpaid';
    case Paused = 'paused';

    /**
     * @throws Exception
     */
    public static function fromString(string $status): self
    {
        return match( $status ){
          'incomplete' => self::Incomplete,
          'incomplete_expired' => self::IncompleteExpired,
          'trialing' => self::Trialing,
          'active' => self::Active,
          'past_due' => self::PastDue,
          'canceled' => self::Cancelled,
          'unpaid' => self::Unpaid,
          'paused' => self::Paused,

          default => throw new Exception("Invalid SusbcriptionStatus '{$status}'"),
        };
    }
}