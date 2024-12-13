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

use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionPaymentStatus;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionStatus;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription;

class CheckoutSessionInformation
{
    public readonly CheckoutSessionStatus $status;
    public readonly CheckoutSessionPaymentStatus $paymentStatus;
    public readonly string $sessionId;
    public readonly array $metadata;
    public readonly ?string $customerEmail;
    public readonly ?string $customerId;
    public readonly ?string $invoiceId;
    public readonly ?string $subscriptionId;

    /**
     * Disable get of non-existent properties.
     * @param $property
     * @throws \Exception
     */
    public function __get( $property )
    {
        throw new \Exception( "Non-existent property {$property} get in " . get_class( $this ) );
    }

    /**
     * Disable set of non-existent properties.
     * @param $property
     * @param $value
     * @throws \Exception
     */
    public function __set( $property, $value )
    {
        throw new \Exception( "Non-existent property {$property} set in " . get_class( $this ) );
    }
    
    /**
     * @param \Stripe\Checkout\Session $session
     * @throws 
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct( \Stripe\Checkout\Session $session )
    {
        // Setup mode not currently supported.
        if( $session->mode == "setup" ){
            throw new \Exception("Stripe Checkout Session setup mode is not supported.");
        }
        $this->sessionId = $session->id;
        $this->status = CheckoutSessionStatus::fromString($session->status);
        $this->paymentStatus = CheckoutSessionPaymentStatus::fromString($session->payment_status);
        $this->invoiceId = self::getStripeObjectId( $session->invoice );
        $this->subscriptionId = self::getStripeObjectId( $session->subscription );
        $this->customerId = self::getStripeObjectId( $session->customer );
        // $session->customer_email is only populated if it was passed as the customer identifier in session creation
        // customer_details is populated if not in setup mode, which we don't support currently.
        $email = $session->customer_details->email;
        $this->customerEmail = $email;
        $this->metadata = $session->metadata?->toArray() ?? [];
    }

    /**
     * Has the Checkout session completed with either successful payment or no payment required?
     * Note that the caller is responsible for avoiding duplicate fulfilments.
     * @return bool
     */
    public function readyForFulfilment(): bool
    {
        return $this->status == CheckoutSessionStatus::Complete &&
            $this->paymentStatus !== CheckoutSessionPaymentStatus::Unpaid;
    }

    /**
     * Resolves a Stripe ID from a Stripe object or ID
     * @param null|string|StripeObject $value The value to resolve.
     * @return null|string The ID
     */
    private static function getStripeObjectId(null|string|StripeObject $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof StripeObject) {
            return $value->id;
        }
        return $value;
    }


}
