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

class LineItem
{
    private string $priceId;
    private int $quantity;

    /**
     * @param string $priceId
     * @param int $quantity
     * @throws Exception
     */
    function __construct( string $priceId, int $quantity )
    {
        if( empty($priceId) || empty($quantity)){
            throw new Exception("LineItems must have a price ID and a quantity.");
        }
        $this->priceId = $priceId;
        $this->quantity = $quantity;
    }

    /**
     * @param LineItem[] $lineItems
     * @throws Exception
     * @return array[]
     */
    public static function objectArrayToStringArray(array $lineItems): array
    {
        if(count($lineItems) < 1){
            throw new Exception("Empty array passed to LineItem::arrayToPrimitives()");
        }
        $out = [];
        foreach ($lineItems as $lineItem){
            $out[] = ['price' => $lineItem->priceId, 'quantity' => $lineItem->quantity];
        }
        return $out;
    }
}