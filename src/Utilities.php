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

namespace PatternSeek\StripeCheckoutFacade;

use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionInformation;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CustomerEmailOrId;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Customer as StripeCustomer;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription;

class Utilities
{
    private readonly StripeClient $stripe;
    private readonly LoggerInterface $log;

    /**
     * @param string $apiSecretKey
     * @param LoggerInterface $log
     */
    function __construct( StripeClient $stripe, LoggerInterface $log )
    {
        $this->stripe = $stripe;
        $this->log = $log;
    }

    public function getOrCreateCustomerIdentification(string $email): ?CustomerEmailOrId
    {
        $searchResult = $this->stripe->customers->search(['query' => \sprintf('email:"%s"', $email)]);
        $count = \count($searchResult->data);
        if ($count === 0) {
            return null;
        }
        if ($count > 1) {
            $this->log->warning(\sprintf('Multiple Stripe customers found for email %s', $email));
        }
        $stripeCustomer = $searchResult->data[0];

        if ($stripeCustomer === null) {
            $customerIdentification = CustomerEmailOrId::email($email);
        } else {
            $customerIdentification = CustomerEmailOrId::stripeCustomerId($stripeCustomer->id);
        }
    }
    
    public function getSession(string $sessionId) : Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }

    public function getSubscription(?string $subscriptionId) : ?Subscription
    {
        if( null === $subscriptionId ){
            return null;
        }
        return $this->stripe->subscriptions->retrieve($subscriptionId, ['expand' => ['default_payment_method']]);
    }

    public function getCustomer(?string $customerId) : ?StripeCustomer
    {
        if( null === $customerId ){
            return null;
        }
        return $this->stripe->customers->retrieve($customerId);
    }

}