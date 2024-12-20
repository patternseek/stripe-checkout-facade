<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
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

use Exception;
use Monolog\Handler\TestHandler;
use PatternSeek\StripeCheckoutFacade\Checkout;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutMode;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionCreateParams;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CustomerEmailOrId;
use PatternSeek\StripeCheckoutFacade\ValueTypes\LineItem;
use PHPUnit\Framework\TestCase;
use Monolog\Level;
use Monolog\Logger;

class CheckoutTest extends TestCase
{

    private Logger $log;
    private array $config;

    protected function setUp(): void
    {
        $this->config = $config = parse_ini_file(__DIR__.'/tests-conf/test.ini');
        $this->log = new Logger('stripe-checkout-facade-tests');
        $this->log->pushHandler(new TestHandler(Level::Info));

    }

    protected function tearDown(): void
    {
    }

    /**
     * @throws Exception
     */
    public function testCreateCheckoutSessionSuccessCustomerEmail()
    {

        $checkout = new Checkout($this->config['apiSecretKey'], $this->log);
        $createParams = new CheckoutSessionCreateParams(
            customerIdentification: CustomerEmailOrId::email($this->config['customerEmail']),
            mode: CheckoutMode::SubscriptionOrMixed,
            returnUrl: $this->config['checkoutReturnUrl']
        );
        $createParams->lineItems[] = new LineItem($this->config['priceId'], 1);
        $sessionClientSecret = $checkout->createCheckoutSession($createParams);
        $this->assertNotEmpty($sessionClientSecret);
    }

    /**
     * @throws Exception
     */
    public function testCreateCheckoutSessionSuccessCustomerId()
    {

        $checkout = new Checkout($this->config['apiSecretKey'], $this->log);
        $createParams = new CheckoutSessionCreateParams(
            customerIdentification: CustomerEmailOrId::stripeCustomerId($this->config['customerId']),
            mode: CheckoutMode::SubscriptionOrMixed,
            returnUrl: $this->config['checkoutReturnUrl']
        );
        $createParams->lineItems[] = new LineItem($this->config['priceId'], 1);
        $sessionClientSecret = $checkout->createCheckoutSession($createParams);
        $this->assertNotEmpty($sessionClientSecret);
    }

    /**
     * @throws Exception
     */
    public function testCreateCheckoutSessionFailBadUrl()
    {
        $this->expectException(Exception::class);
        
        $checkout = new Checkout($this->config['apiSecretKey'], $this->log);
        $createParams = new CheckoutSessionCreateParams(
            customerIdentification: CustomerEmailOrId::stripeCustomerId($this->config['customerId']),
            mode: CheckoutMode::SubscriptionOrMixed,
            returnUrl: 'http://url.without.return.page.com/'
        );
        $createParams->lineItems[] = new LineItem($this->config['priceId'], 1);
        $checkout->createCheckoutSession($createParams);
    }

    /**
     * @throws Exception
     */
    public function testCreateCheckoutSessionInvalidPrice()
    {
        $this->expectException(Exception::class);

        $checkout = new Checkout($this->config['apiSecretKey'], $this->log);
        $createParams = new CheckoutSessionCreateParams(
            customerIdentification: CustomerEmailOrId::stripeCustomerId($this->config['customerId']),
            mode: CheckoutMode::SubscriptionOrMixed,
            returnUrl: $this->config['checkoutReturnUrl']
        );
        $createParams->lineItems[] = new LineItem("INVALID PRICE ID", 1);
        $checkout->createCheckoutSession($createParams);
    }

    
}
