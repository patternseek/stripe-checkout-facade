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

use Psr\Log\LoggerInterface;
use Stripe\Customer as StripeCustomer;
use Stripe\StripeClient;

class Customer
{
    private readonly StripeClient $stripe;
    private readonly LoggerInterface $log;

    /**
     * @param string $apiSecretKey
     * @param LoggerInterface $log
     */
    function __construct(string $apiSecretKey, LoggerInterface $log)
    {
        $this->stripe = new StripeClient($apiSecretKey);
        $this->log = $log;
    }

    public function lookupCustomerByEmail(string $email): ?StripeCustomer
    {
        $searchResult = $this->stripe->customers->search(['query' => \sprintf('email:"%s"', $email)]);
        if ($searchResult->total_count === 0) {
            return null;
        }
        if ($searchResult->total_count > 1) {
            $this->log->warning(\sprintf('Multiple Stripe customers found for email %s', $email));
        }
        return $searchResult->data[0];
    }
}
