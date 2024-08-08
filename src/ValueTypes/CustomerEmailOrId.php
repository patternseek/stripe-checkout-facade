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

class CustomerEmailOrId
{

    public readonly CustomerIdentifierType $type;
    private ?string $email;
    private ?string $stripeCustomerId;

    /**
     * @param CustomerIdentifierType $type
     * @param string|null $email
     * @param string|null $stripeCustomerId
     */
    private function __construct( CustomerIdentifierType $type, ?string $email, ?string $stripeCustomerId )
    {
        $this->type = $type;
        $this->email = $email;
        $this->stripeCustomerId = $stripeCustomerId;
    }

    /**
     * @param string $email
     * @return CustomerEmailOrId
     * @throws Exception
     */
    public static function email( string $email ): CustomerEmailOrId{
        if( empty($email) ){
            throw new Exception('$email must not be empty when calling CustomerEmailOrId::email()');
        }
        return new self( CustomerIdentifierType::Email, $email, null );
    }

    /**
     * @param string $stripeCustomerId
     * @return CustomerEmailOrId
     * @throws Exception
     */
    public static function stripeCustomerId( string $stripeCustomerId ): CustomerEmailOrId
    {
        if( empty($stripeCustomerId) ){
            throw new Exception('$stripeCustomerId must not be empty when calling CustomerEmailOrId::stripeCustomerId()');
        }
        return new self( CustomerIdentifierType::StripeCustomerId, null, $stripeCustomerId );
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return match ($this->type){
            CustomerIdentifierType::Email => $this->email,
            CustomerIdentifierType::StripeCustomerId => $this->stripeCustomerId,
        };
    }
}