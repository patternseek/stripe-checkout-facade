<?php
require_once __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PatternSeek\StripeCheckoutFacade\Checkout;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutMode;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionCreateParams;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CustomerEmailOrId;
use PatternSeek\StripeCheckoutFacade\ValueTypes\LineItem;
use Psr\Log\LoggerInterface;

// Config via dotenv
// Doesn't seem to respect actual env if the file doesn't exist so I'm adding this conditional
if( file_exists(__DIR__.'/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$apiSecretKey = $_ENV['apiSecretKey'];
$apiPublicKey = $_ENV['apiPublicKey'];
$checkoutEndpointSecret = $_ENV['checkoutEndpointSecret'];
$subscriptionEndpointSecret = $_ENV['subscriptionEndpointSecret'];
$priceId = $_ENV['priceId'];
$checkoutReturnUrl = $_ENV['checkoutReturnUrl'];
$portalReturnUrl = $_ENV['portalReturnUrl'];

// Set up logging
$log = new Logger('stripe-checkout-facade-demo-app');
$log->pushHandler(new StreamHandler('/tmp/stripe-checkout-facade-demo-app.log', Level::Info));

try{
    // Parse customer ID type and value from config
    $customerIdentification = match( $_ENV['customerIdMode'] ){
        'email' => CustomerEmailOrId::email($_ENV['customerEmail']),
        'id' => CustomerEmailOrId::stripeCustomerId($_ENV['customerId']),
        default => throw new Exception("Invalid customerIdMode in config")
    };
    
    // Routing to handler functions
    $output = match ($_GET['route']??''){
        default => checkoutStart( $log, $apiPublicKey, $apiSecretKey, $customerIdentification, $priceId, $checkoutReturnUrl ),
        'returnPage' => returnPage($log, $apiSecretKey, $_GET['sessionId']),
        'sessionWebhookEndpoint' => 
            sessionWebhookEndpoint(
                log: $log, 
                apiSecretKey: $apiSecretKey,
                endpointSecret: $checkoutEndpointSecret, 
                postBody: file_get_contents('php://input'), 
                httpSignatureHeader: $_SERVER['HTTP_STRIPE_SIGNATURE']),
        'subscriptionWebhookEndpoint' => 
            subscriptionWebhookEndpoint(
                log: $log,
                apiSecretKey: $apiSecretKey,
                endpointSecret: $subscriptionEndpointSecret,
                postBody: file_get_contents('php://input'),
                httpSignatureHeader: $_SERVER['HTTP_STRIPE_SIGNATURE']),
        'redirectToCustomerPortal' =>
            redirectToCustomerPortal(
                $log,
                $apiSecretKey,
                CustomerEmailOrId::stripeCustomerId($_GET['customerId']),
                $portalReturnUrl
            ),
    };
    
    // Content-type headers are set in handler functions
    echo $output;
    
}catch (Exception $e){
    $err = "Client application received exception: " . $e->getMessage();
    $log->alert($err);
    return $err;
}
    
    
// ---------- Page handler functions --------------

/**
 * Initialise session and render the embedded checkout component
 *
 * @param LoggerInterface $log
 * @param $apiPublicKey
 * @param $apiSecretKey
 * @param string|null $customerId
 * @param string $customerEmail
 * @param $priceId
 * @param $returnUrl
 * @return string
 * @throws Exception
 */
function checkoutStart( LoggerInterface $log, $apiPublicKey, $apiSecretKey, CustomerEmailOrId $customerIdentification, $priceId, $returnUrl ): string
{
    $checkout = new Checkout($apiSecretKey, $log);
    $createParams = new CheckoutSessionCreateParams(
        customerIdentification: $customerIdentification,
        mode: CheckoutMode::SubscriptionOrMixed,
        returnUrl: $returnUrl
    );
    $createParams->lineItems[] = new LineItem($priceId, 1);
    $sessionClientSecret = $checkout->createCheckoutSession($createParams);

    ob_start();
    // uses $apiPublicKey and $sessionClientSecret
    include __DIR__.'/../views/checkoutStart.php';
    return ob_get_clean();
        
}

/**
 * Handle the user returning from the checkout
 *
 * @param LoggerInterface $log
 * @param $apiSecretKey
 * @param $sessionId
 * @return string
 * @throws Exception
 */
function returnPage( LoggerInterface $log, $apiSecretKey,$sessionId ): string
{

    $checkout = new Checkout($apiSecretKey, $log);
    $sessionInfo = $checkout->retrieveCheckoutSessionInfo($sessionId);
    
    // Did the checkout session complete and was payment successful (or not needed)?
    if( $sessionInfo->readyForFulfilment() ){
        // TODO *** You would do fulfilment here. Fulfilment function needs to be idempotent as it will also be called by webhook handlers ***
    }
    
    ob_start();
    // Uses $sessionInfo
    include '../views/returnPage.php';
    return ob_get_clean();

}

/**
 * Redirect to customer portal for the passed customer id
 * 
 * @param LoggerInterface $log
 * @param $apiSecretKey
 * @param CustomerEmailOrId $customerId
 * @param string $returnUrl
 * @return string
 * @throws Exception
 */
function redirectToCustomerPortal( LoggerInterface $log, $apiSecretKey, CustomerEmailOrId $customerId, string $returnUrl): string
{
    $checkout = new Checkout($apiSecretKey, $log);
    header( "Location: ". $checkout->createBillingPortalSessionUrl( $customerId, $returnUrl ) );
    return "";
}

/**
 * Handle checkout session webhook events
 * 
 * @param LoggerInterface $log
 * @param $apiSecretKey
 * @param $endpointSecret
 * @param $postBody
 * @param $httpSignatureHeader
 * @return string
 * @throws Exception
 */
function sessionWebhookEndpoint( LoggerInterface $log, $apiSecretKey, $endpointSecret, $postBody, $httpSignatureHeader): string
{
    $checkout = new Checkout($apiSecretKey, $log);
    $webhookResponse = $checkout->sessionWebhookHandler(
        rawPost: $postBody,
        httpSignatureHeader: $httpSignatureHeader,
        endpointSecret: $endpointSecret
    );
    if( $webhookResponse->sessionInformation->readyForFulfilment() ){
        // TODO *** You would do fulfilment here.
        // Fulfilment function needs to be idempotent as it will also be called at the return page,
        // and by the subscription webhook handler. However the difference between this handler and
        // the subscription handler is that if you have non-subscription items they can be fulfilled
        // here and in the return page ***
        $log->info("Fulfilled checkout session {$webhookResponse->sessionInformation->sessionId} for customer {$webhookResponse->sessionInformation->customer} with email {$webhookResponse->sessionInformation->customerEmail}");
    }else{
        $log->error("Fulfilment failure", [$webhookResponse]);
    }
    // Generate webhook response for Stripe
    header("Content-Type: application/json");
    http_response_code($webhookResponse->response->code);
    return $webhookResponse->response->jsonResponseStr;
}

/**
 * Handle subscription webhook events
 * 
 * @param LoggerInterface $log
 * @param $apiSecretKey
 * @param $endpointSecret
 * @param $postBody
 * @param $httpSignatureHeader
 * @return string
 * @throws Exception
 */
function subscriptionWebhookEndpoint( LoggerInterface $log, $apiSecretKey, $endpointSecret, $postBody, $httpSignatureHeader): string
{
    $checkout = new Checkout($apiSecretKey, $log);
    $webhookResponse = $checkout->subscriptionWebhookHandler(
        rawPost: $postBody,
        httpSignatureHeader: $httpSignatureHeader,
        endpointSecret: $endpointSecret
    );
    if( $webhookResponse->subscriptionInformation->isInGoodStanding() ){
        // TODO *** You would do fulfilment here.
        // Fulfilment function needs to be idempotent as it will also be called at the return page,
        // and by the subscription webhook handler. However the difference between this handler and
        // the subscription handler is that if you have non-subscription items they can be fulfilled
        // here and in the return page ***
        $log->info("Fulfilled subscription {$webhookResponse->subscriptionInformation->subscriptionId} for customer {$webhookResponse->subscriptionInformation->customer}");
    }else{
        $log->error("Fulfilment failure", [$webhookResponse]);
    }
    // Generate webhook response for Stripe
    header("Content-Type: application/json");
    http_response_code($webhookResponse->response->code);
    return $webhookResponse->response->jsonResponseStr;
}