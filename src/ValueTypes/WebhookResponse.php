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

class WebhookResponse
{
    public bool $success;
    public int $code;
    public string $jsonResponseStr;
    
    public function __construct( bool $success, int $code, array $response )
    {
        $this->success = $success;
        $this->code = $code;
        $this->jsonResponseStr = json_encode($response);
    }
}