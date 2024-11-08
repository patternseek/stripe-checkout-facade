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
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription;

class CheckoutSessionInformation
{
    public readonly Session $session;
    public readonly CheckoutSessionStatus $status;
    public readonly CheckoutSessionPaymentStatus $paymentStatus;
    public readonly ?string $customerEmail;
    public readonly array $metadata;
    public readonly ?Customer $customer;
    public readonly ?Invoice $invoice;
    public readonly ?PaymentMethod $paymentMethod;
    public readonly ?Subscription $subscription;

    /**
     * @param Session $session
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function __construct(Session $session, StripeClient $stripe)
    {
        if ($session->line_items === null || $session->line_items->count() === 0) {
            $session->line_items = $stripe->checkout->sessions->allLineItems($session->id);
        }
        $this->session = $session;
        $this->invoice = self::resolveStripeObject(
            $session->invoice,
            static fn(string $id) => $stripe->invoices->retrieve($id)
        );
        $this->subscription = self::resolveStripeObject(
            $session->subscription,
            static fn(string $id) => $stripe->subscriptions->retrieve($id, ['expand' => ['default_payment_method']])
        );
        $this->customer = self::resolveStripeObject(
            $session->customer,
            static fn(string $id) => $stripe->customers->retrieve($id)
        );

        if ($this->subscription !== null) {
            $this->subscription->default_payment_method = self::resolveStripeObject(
                $this->subscription->default_payment_method,
                static fn(string $id) => $stripe->paymentMethods->retrieve($id)
            );
        }

        $this->paymentMethod = $this->subscription?->default_payment_method;
        $this->status = CheckoutSessionStatus::fromString($session->status);
        $this->paymentStatus = CheckoutSessionPaymentStatus::fromString($session->payment_status);
        // $session->customer_email is only populated if it was passed as the customer identifier in session creation
        $email = $session->customer_details?->email ?? $this->customer?->email;
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
     * Resolves a Stripe object from a string ID.
     * @param null|string|StripeObject $value The value to resolve.
     * @param callable $resolver The function to call to resolve the value ID.
     * @return null|StripeObject The resolved object or null if the value was null.
     */
    private static function resolveStripeObject(null|string|StripeObject $value, callable $resolver): ?StripeObject
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof StripeObject) {
            return $value;
        }
        return $resolver($value);
    }
}
