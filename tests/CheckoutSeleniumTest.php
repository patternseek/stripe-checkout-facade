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
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use phpseclib3\File\ASN1\Maps\PrivateDomainName;
use PHPUnit\Framework\TestCase;
use getinstance\fbwebdriverfacade\SeleniumLib;

class CheckoutSeleniumTest extends TestCase
{

    private RemoteWebDriver $driver;
    private SeleniumLib $tools;

    /**
     * @param string $selector
     * @return RemoteWebElement
     */
    private function byCss(string $selector): RemoteWebElement
    {
        return $this->driver->findElement(
            WebDriverBy::cssSelector($selector)
        );
    }

    /**
     * @param string $path
     * @return RemoteWebElement
     */
    private function byXPath(string $path): RemoteWebElement
    {
        return $this->driver->findElement(
            WebDriverBy::xpath($path)
        );
    }

    protected function setUp(): void
    {
        $host = "http://127.0.0.1:4444/wd/hub";
        $capabilities = DesiredCapabilities::chrome();
        $this->driver = RemoteWebDriver::create($host, $capabilities);
        $this->driver->manage()->window()->fullscreen();
        $this->tools = new SeleniumLib($this->driver);
    }

    protected function tearDown(): void
    {
        $this->driver->quit();
    }

    public function testPaymentNormalGB()
    {
        $this->doPaymentPage(
            cardNumber: '4242424242424242',
            expiry: "12/45",
            billingCountryCode: 'GB',
            billingPostalCode: 'BN1 1AA',
            authShouldShow: false,
            succeed3dAuth: true,
            vatShouldBeNonZero: true
        );
    }

    public function testPaymentNormalES()
    {
        $this->doPaymentPage(
            cardNumber: '4242424242424242',
            expiry: "12/45",
            billingCountryCode: 'ES',
            billingPostalCode: null,
            authShouldShow: false,
            succeed3dAuth: true,
            vatShouldBeNonZero: false
        );
    }

    public function testPaymentNormalNZ()
    {
        $this->doPaymentPage(
            cardNumber: '4242424242424242',
            expiry: "12/45",
            billingCountryCode: 'NZ',
            billingPostalCode: null,
            authShouldShow: false,
            succeed3dAuth: true,
            vatShouldBeNonZero: false
        );
    }


    /**
     * NOTE This test is regularly failing with the broser error "Expired session"
     * NOTE This is a Stripe bug, it happens when using 3d secure on their official checkout demo page too
     * 
     * 
     * @return void
     */
//    public function testPayment3dAuthSuccess()
//    {
//
//        // Succeed auth
//        $this->doPaymentPage(
//            cardNumber: '4000002760003184', // requires auth
//            expiry: "12/45",
//            billingCountryCode: 'GB',
//            billingPostalCode: 'BN1 1AA',
//            authShouldShow: true,
//            succeed3dAuth: true,
//            vatShouldBeNonZero: true
//        );
//    }
//
    public function testPayment3dAuthFailure()
    {
        
        // Fail auth
        $this->doPaymentPage(
            cardNumber: '4000002760003184', // requires auth
            expiry: "12/45",
            billingCountryCode: 'GB',
            billingPostalCode: 'BN1 1AA',
            authShouldShow: true,
            succeed3dAuth: false,
            vatShouldBeNonZero: true
        );
        
    }

    /**
     * @param string $cardNumber
     * @param string $expiry
     * @param string $billingCountryCode
     * @param string $billingPostalCode
     * @return void
     */
    private function doPaymentPage(
        string $cardNumber,
        string $expiry,
        string $billingCountryCode,
        ?string $billingPostalCode,
        bool $authShouldShow,
        bool $succeed3dAuth,
        bool $vatShouldBeNonZero
    ): void{

        try{
            $billingName = "Just Some Guy You Know";

            $checkoutFrameCss = '#checkout > iframe';
            $cardId = 'cardNumber';
            $cardExpId = 'cardExpiry';
            $cardCvcId = 'cardCvc';
            $billingNameId = 'billingName';
            $billingCountryId = 'billingCountry'; // (select)
            $billingPostalCodeId = 'billingPostalCode';
            $vatSelectorNonZero = '.OrderDetailsSubtotalItem > div:nth-child(2) > span:nth-child(1) > span:nth-child(1)';
            $vatSelectorZero = '.Link--secondary > span:nth-child(1) > span:nth-child(1)';
            $manageBillingButtonCss = 'body > form:nth-child(3) > button:nth-child(1)';
            $manageBillingCountryFieldCss = 'div.Margin-top--24:nth-child(3) > div:nth-child(1) > div:nth-child(2) > div:nth-child(1) > address:nth-child(1) > div:nth-child(2) > div:nth-child(1) > div:nth-child(1) > div:nth-child(1) > div:nth-child(1)';

            $t = $this->tools;

            $this->driver->get("http://host.docker.internal:4242/");

            $t->waitForCss($checkoutFrameCss);
            $this->driver->switchTo()->frame($t->getElementByCSS($checkoutFrameCss));

            $t->waitForId($cardId);
            $t->typeInAtId($cardId, $cardNumber);
            $t->typeInAtId($cardExpId, $expiry);
            $t->typeInAtId($cardCvcId, "123");

            $t->typeInAtId($billingNameId, $billingName);
            $t->pickFromSelectAtElement($t->getElementById($billingCountryId), $billingCountryCode);

            if (null !== $billingPostalCode) {
                $t->typeInAtId($billingPostalCodeId, $billingPostalCode);
            }

            // Verify VAT
            if ($vatShouldBeNonZero) {
                $t->waitForCss($vatSelectorNonZero);
                sleep(4);
                $vatElText = $t->getElementByCSS($vatSelectorNonZero)->getText();
                $this->assertTrue(
                    (false === stristr($vatElText, "£0.00"))
                );
            }else {
                $t->waitForCss($vatSelectorZero);
                sleep(1);
                $vatElText = $t->getElementByCSS($vatSelectorZero)->getText();
                $this->assertTrue(
                    is_string(stristr($vatElText, "£0.00"))
                );
            }

            $t->waitForClassVisible('SubmitButton--complete');
            $t->clickAtClass('SubmitButton--complete');

            // Handle 3D Secure auth 
            if ($authShouldShow) {

                $this->driver->switchTo()->defaultContent();
                $t->waitForCssVisible('iframe[name*="__privateStripeFrame"]');
                $this->driver->switchTo()->frame($t->getElementByCSS('iframe[name*="__privateStripeFrame"]'));
                $t->waitForIdVisible('challengeFrame');
                $this->driver->switchTo()->frame($t->getElementById('challengeFrame'));
                $t->waitForIdVisible('test-source-authorize-3ds');
                if ($succeed3dAuth) {
                    //$t->clickAtId('test-source-authorize-3ds');
                }else {
                    $t->clickAtId('test-source-fail-3ds');

                    // Frame switch
                    $this->driver->switchTo()->defaultContent();
                    $t->waitForCss($checkoutFrameCss);
                    $this->driver->switchTo()->frame($t->getElementByCSS($checkoutFrameCss));

                    // Check for error
                    $t->waitForClassVisible('Notice-message');
                    $msg = $t->getElementByClass('Notice-message')->getText();
                    $this->assertStringContainsString('We are unable to authenticate your payment method.', $msg);

                    return;
                }
            }

            // Parse session status
            $this->driver->switchTo()->defaultContent();
            $t->waitForCss(".resultText");
            $bodyText = $t->getElementByCSS('body')->getText();

            $this->assertTrue(is_string(stristr($bodyText, '[value] => complete')), $bodyText);
            $this->assertTrue(is_string(stristr($bodyText, '[value] => paid')), $bodyText);

            // Go to manage billing page
            $t->clickAtCss($manageBillingButtonCss);
            $t->waitForCssVisible('.Icon--business-svg');
        }catch (Exception $e ){
            $filename = '/tmp/failure_'.uniqid().'.png';
            $this->driver->takeScreenshot($filename);
            throw new Exception("Exception while executing test. Screenshot saved to {$filename}.", 0, $e);
        }

    }

}
