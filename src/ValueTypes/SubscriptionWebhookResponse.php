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

class SubscriptionWebhookResponse
{
    public WebhookResponse $response;
    public ?SubscriptionInformation $subscriptionInformation;
    
    public function __construct( WebhookResponse $response, ?SubscriptionInformation $subscriptionInformation )
    {
        $this->response = $response;
        $this->subscriptionInformation = $subscriptionInformation;
    }

}