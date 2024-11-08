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
 * Represents the parameters required to create a Stripe Checkout session.
 */
class CheckoutSessionCreateParams
{
    public readonly CustomerEmailOrId $customerIdentification;
    public readonly CheckoutMode $mode;
    public readonly string $returnUrl;
    /**
     * @var array<LineItem>
     */
    public array $lineItems = [];
    public CheckoutLocale $locale = CheckoutLocale::auto;
    public bool $useStripeTax = true;
    public bool $allowPromotionCodes = true;
    public bool $billingAddressRequired = false;
    /**
     * @var array<string,string>
     */
    public array $metadata = [];

    /**
     * Constructs a set of parameters for creating a Stripe Checkout session.
     *
     * @param CustomerEmailOrId $customerIdentification Whether to identify the customer by email or Stripe customer ID.
     * @param CheckoutMode      $mode                   The mode of the checkout session.
     * @param string            $returnUrl              The URL to redirect to after the checkout session is completed.
     */
    public function __construct(CustomerEmailOrId $customerIdentification, CheckoutMode $mode, string $returnUrl)
    {
        if (false === \mb_strstr($returnUrl, '{CHECKOUT_SESSION_ID}')) {
            $errMsg = 'returnUrl must contain {CHECKOUT_SESSION_ID}.';
            $errMsg .= ' See https://docs.stripe.com/payments/checkout/custom-redirect-behavior#return-url';
            throw new \InvalidArgumentException($errMsg);
        }

        $this->customerIdentification = $customerIdentification;
        $this->mode = $mode;
        $this->returnUrl = $returnUrl;
    }

    /**
     * Converts this object to an array suitable for passing to the Stripe API.
     *
     * @return array<string,mixed> The array of parameters.
     */
    public function toApiParams(): array
    {
        if (\count($this->lineItems) === 0) {
            throw new \InvalidArgumentException('At least one line item must be added to the checkout session.');
        }

        $firstLineItem = $this->lineItems[0];
        $lineItems = $this->lineItems;
        if ($firstLineItem instanceof LineItem) {
            $lineItems = LineItem::objectArrayToStringArray($lineItems);
        }
        $params = [
            'ui_mode' => 'embedded',
            'line_items' => $lineItems,
            'mode' => $this->mode->value,
            'locale' => $this->locale->value,
            'return_url' => $this->returnUrl,
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'customer_update' => [
                'address' => 'auto',
                'name' => 'auto',
                'shipping' => 'auto',
            ],
        ];
        $paramKey = match ($this->customerIdentification->type) {
            CustomerIdentifierType::Email => 'customer_email',
            CustomerIdentifierType::StripeCustomerId => 'customer',
        };
        $params[$paramKey] = $this->customerIdentification->value();
        if ($this->useStripeTax) {
            $params['automatic_tax'] = ['enabled' => true];
        }
        if ($this->allowPromotionCodes) {
            $params['allow_promotion_codes'] = true;
        }
        if ($this->billingAddressRequired) {
            $params['billing_address_collection'] = 'required';
        }
        if (\count($this->metadata) > 0) {
            $params['metadata'] = $this->metadata;
        }

        return $params;
    }
}
