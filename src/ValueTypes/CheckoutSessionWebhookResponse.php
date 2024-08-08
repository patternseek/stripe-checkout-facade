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

class CheckoutSessionWebhookResponse
{
    public WebhookResponse $response;
    public ?CheckoutSessionInformation $sessionInformation;
    
    public function __construct( WebhookResponse $response, ?CheckoutSessionInformation $sessionInformation )
    {
        $this->response = $response;
        $this->sessionInformation = $sessionInformation;
    }

}