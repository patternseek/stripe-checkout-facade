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

namespace PatternSeek\StripeCheckoutFacade;

use Exception;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionCreateParams;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CustomerEmailOrId;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CustomerIdentifierType;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionInformation;
use PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionWebhookResponse;
use PatternSeek\StripeCheckoutFacade\ValueTypes\SubscriptionInformation;
use PatternSeek\StripeCheckoutFacade\ValueTypes\SubscriptionWebhookResponse;
use PatternSeek\StripeCheckoutFacade\ValueTypes\WebhookResponse;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class Checkout
{

    private StripeClient $stripe;
    private LoggerInterface $log;

    /**
     * @param string $apiSecretKey
     * @param LoggerInterface $log
     */
    function __construct( string $apiSecretKey, LoggerInterface $log )
    {
        $this->stripe = new StripeClient($apiSecretKey);
        $this->log = $log;
    }

    /**
     * Creates a session and returns the client secret as a string.
     *
     * @param CheckoutSessionCreateParams $params            The parameters for creating the session.
     * @param bool                        $returnFullSession If true, the full session object is returned instead of
     *                                                       just the client secret.
     * @return string|Session The client secret or the full session object.
     */
    public function createCheckoutSession(
        CheckoutSessionCreateParams $params,
        bool $returnFullSession = false
    ): string|Session {
        try {
            $sessionSpec = $params->toApiParams();
            $session = $this->stripe->checkout->sessions->create($sessionSpec);
        } catch (\Throwable $e) {
            $this->log->alert($e->getMessage());
            throw $e;
        }

        return $returnFullSession ? $session : $session->client_secret;
    }

    /**
     * Get session information
     * 
     * @param string $sessionId
     * @return CheckoutSessionInformation
     * @throws Exception
     */
    public function retrieveCheckoutSessionInfo( string $sessionId ): CheckoutSessionInformation
    {
        try{
            $session = $this->stripe->checkout->sessions->retrieve( $sessionId, ['expand' => ['line_items']] );
            $sessionInformation = new CheckoutSessionInformation( $session );
        }catch (Exception $e ){
            $this->log->alert($e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        return $sessionInformation;

    }

    /**
     * Create a short lived billing portal URL.
     * Note that the page calling this function should ideally be accessed via POST so that it is not cached
     * as the URL that's returned is ephemeral.
     * 
     * @param CustomerEmailOrId $customerIdentification
     * @param string $returnUrl
     * @return string
     * @throws Exception
     */
    public function createBillingPortalSessionUrl( CustomerEmailOrId $customerIdentification, string $returnUrl ): string
    {
        try{
            // We need a customer ID, not an email
            if( $customerIdentification->type == CustomerIdentifierType::Email ){
                throw new Exception("createBillingPortalSessionUrl() requires a Stripe Customer ID, not an email.");
            }
            $session = $this->stripe->billingPortal->sessions->create([
                'customer'=>$customerIdentification->value(),
                'return_url'=>$returnUrl,
            ]);
            return $session->url;
        }catch (Exception $e ){
            $this->log->alert($e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * This method is only meant to process the callbacks specific to Checkout.
     * The intention is that you should set up a webhook listener specifically for:
     * 'checkout.session.completed' and 'checkout.session.async_payment_succeeded'
     * 
     * @param string $rawPost
     * @param string $httpSignatureHeader
     * @param string $endpointSecret
     * @return CheckoutSessionWebhookResponse
     * @throws Exception
     */
    public function sessionWebhookHandler( string $rawPost, string $httpSignatureHeader, string $endpointSecret ): CheckoutSessionWebhookResponse
    {
        $eventOrResponse = $this->extractWebhookEvent($rawPost, $httpSignatureHeader, $endpointSecret);
        
        if( $eventOrResponse::class == WebhookResponse::class ){
            // Failure
            return new CheckoutSessionWebhookResponse( $eventOrResponse, null);
        }else{
            $event = $eventOrResponse;
        }

        try{
            if (
                $event->type == 'checkout.session.completed'
                || $event->type == 'checkout.session.async_payment_succeeded'
            ) {
                // This is the happy path
                return new CheckoutSessionWebhookResponse( 
                    new WebhookResponse( true, 200, ['success'=>true] ), 
                    new CheckoutSessionInformation($event->data->object )
                );
            }else{
                $err = "Invalid webhook event for checkout session endpoint: Endpoint received {$event->type} event but only supports handling 'checkout.session.completed' and 'checkout.session.async_payment_succeeded'";
                $this->log->alert($err);
                return new CheckoutSessionWebhookResponse(
                    new WebhookResponse( false, 400, ['error'=>$err] ),
                    null
                );
            }
        }catch (Exception $e ){
            $this->log->alert($e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        
    }

    /**
     * @param string $rawPost
     * @param string $httpSignatureHeader
     * @param string $endpointSecret
     * @return SubscriptionWebhookResponse
     * @throws Exception
     */
    public function subscriptionWebhookHandler( string $rawPost, string $httpSignatureHeader, string $endpointSecret ): SubscriptionWebhookResponse
    {
        $eventOrResponse = $this->extractWebhookEvent($rawPost, $httpSignatureHeader, $endpointSecret);

        if( $eventOrResponse::class == WebhookResponse::class ){
            // Failure
            return new SubscriptionWebhookResponse( $eventOrResponse, null);
        }else{
            $event = $eventOrResponse;
        }

        try{
            if ( 
                $event->type == 'customer.subscription.updated'
                || $event->type == 'customer.subscription.deleted'
            ) {
                // This is the happy path
                return new SubscriptionWebhookResponse(
                    new WebhookResponse( true, 200, ['success'=>true] ),
                    new SubscriptionInformation($event->data->object )
                );
            }else{
                // Unsupported webhook
                $err = "Invalid webhook event for subscription endpoint: Endpoint received {$event->type} event but only supports handling 'customer.subscription.updated' and 'customer.subscription.deleted'";
                $this->log->alert($err);
                return new SubscriptionWebhookResponse(
                    new WebhookResponse( false, 400, ['error'=>$err] ),
                    null
                );
            }
        }catch (Exception $e ){
            $this->log->alert($e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $rawPost
     * @param string $httpSignatureHeader
     * @param string $endpointSecret
     * @return WebhookResponse|\Stripe\Event
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    protected function extractWebhookEvent(
        string $rawPost,
        string $httpSignatureHeader,
        string $endpointSecret
    ): \Stripe\Event | WebhookResponse
    {
        
        try{
            $event = Webhook::constructEvent(
                $rawPost, $httpSignatureHeader, $endpointSecret
            );
        }catch (UnexpectedValueException $e){
            // Invalid payload
            $err = 'Error parsing Stripe webhook payload: ' . $e->getMessage();
            $this->log->alert($err);
            return new WebhookResponse(false, 400, ['error' => $err]);
        }catch (SignatureVerificationException $e){
            // Invalid signature
            $err = 'Error verifying Stripe webhook signature: ' . $e->getMessage();
            $this->log->alert($err);
            return new WebhookResponse(false, 400, ['error' => $err]);
        }
        return $event;
    }
}
