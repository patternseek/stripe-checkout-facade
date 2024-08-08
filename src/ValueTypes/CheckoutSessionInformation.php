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

class CheckoutSessionInformation
{

    public CheckoutSessionStatus $status;
    public CheckoutSessionPaymentStatus $paymentStatus;
    public string $sessionId;
    public string $customer;
    public string $customerEmail;
    public ?array $metadata;
    

    /**
     * @param \Stripe\Checkout\Session $session
     * @throws Exception
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct( \Stripe\Checkout\Session $session )
    {
        $this->status = CheckoutSessionStatus::fromString($session->status);
        $this->paymentStatus = CheckoutSessionPaymentStatus::fromString($session->payment_status);
        $this->sessionId = $session->id;
        $this->customer = $session->customer;
        // $session->customer_email is only populated if it was passed as the customer identifier in session creation
        $this->customerEmail = $session->customer_details->email; 
        $this->metadata = $session->metadata->toArray();
    }

    /**
     * Has the Checkout session completed with either successful payment or no payment required?
     * Note that the caller is responsible for avoiding duplicate fulfilments.
     * @return bool
     */
    public function readyForFulfilment(): bool
    {
        return $this->status == CheckoutSessionStatus::Complete && $this->paymentStatus !== CheckoutSessionPaymentStatus::Unpaid;
    }

}