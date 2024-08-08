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
use Stripe\Subscription;

class SubscriptionInformation
{

    public SubscriptionStatus $status;
    public string $subscriptionId;
    public string $customer;
    public bool $cancelAtPeriodEnd;
    public string $currency;
    public int $currentPeriodStartTimestamp;
    public int $currentPeriodEndTimestamp;
    public ?string $defaultPaymentMethod;
    
    public ?array $metadata;
    

    /**
     * @param \Stripe\Checkout\Session $subscription
     * @throws Exception
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct( \Stripe\Subscription $subscription )
    {
        
        $this->status = SubscriptionStatus::fromString($subscription->status);
        $this->subscriptionId = $subscription->id;
        $this->customer = $subscription->customer;
        $this->cancelAtPeriodEnd = $subscription->cancel_at_period_end;
        $this->currency = $subscription->currency;
        $this->currentPeriodStartTimestamp = $subscription->current_period_start;
        $this->currentPeriodEndTimestamp = $subscription->current_period_end;
        $this->defaultPaymentMethod = $subscription->default_payment_method;
        $this->metadata = $subscription->metadata->toArray();
    }

    /**
     * Is the subscription currently in a state where the user should receive the associated benefits?
     * 
     * @return bool
     */
    public function isInGoodStanding(): bool
    {
        return match ($this->status ){
            SubscriptionStatus::Trialing, SubscriptionStatus::Active => true,
            default => false,
        };
    }


}