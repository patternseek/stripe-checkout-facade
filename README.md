# Stripe Checkout Facade

Provides a facade around:
- Stripe Checkout
- some Checkout webhook events
- some Subscription webhook events
- the Stripe Customer Portal

The aim is to make it as difficult as possible to mis-configure or misuse any of these Stripe interactions.
Additionally all internal errors log to the alert channel of the provided PSR logging utility.

## Overview

See the demo app in `/demo-app/web/index.php` for a sample implementation of everything in this section. It's under 150
line of code, plus two templates, so it's not too hard to follow.

### Checkout session

The flow is as follows:

- Instantiate the Checkout class and call `createCheckoutSession()`. This returns a Session client secret. See `checkoutStart()` in `demo-app/web/index.php`.
- Use the session client secret to initialise the Stripe Checkout JS, in your template. See `demo-app/views/checkoutStart.php`.
- When the user finishes the Checkout session, whether it's successful or not, they will be returned to the URL passed
  in the `returnUrl` parameter in the initial call to `createCheckoutSession()`. The return page will receive the Session ID 
  in the GET parameters, as defined by the `{CHECKOUT_SESSION_ID}` template value in the `returnUrl` parameter.
- You can then use the session ID parameter returned by Stripe to retrieve the final state of the session, using the `retrieveCheckoutSessionInfo()` 
  method on your `Checkout` instance. This returns a `CheckoutSessionInformation` object with various information you may want to store some of.
- The `CheckoutSessionInformation` object has a function `readyForFulfilment()` which verifies that the session completed and the payment was
  successful. This can be used to perform immediate fulfilment if needed. However bear in mind you should also be listening for the following
  webhook events as the Checkout Session isn't guaranteed to return to your site if the user has a network error.

### Webhooks

The `Checkout` class includes helper functions for handling a specific subset of Stripe webhook events that are needed for
fulfilment of regular products and for subscriptions, including subscription renewals, cancellations, modifications and failures.

There are two functions, one for `checkout.session.completed` and `checkout.session.async_payment_succeeded`, and one for 
`customer.subscription.updated` and `customer.subscription.deleted`. The former are useful for knowing that the payment was successful
in the Checkout process, which can be used for instant fulfilment of subscription and regular products if the user fails to redirect
back at the end of the Checkout session, and the latter can be used to receive notifications of new, updated and ended subscriptions.
Note that for subscriptions, the `customer.subscription.updated` event can refer to creation, modification, renewal and expiry, however
not all conditions that end a subscription are included in that event, hence the need to also listen for `customer.subscription.deleted`.

The way to use these is to set up two endpoints in Stripe, one for each set of events, and then use the `Checkout::sessionWebhookHandler()`
(see `sessionWebhookEndpoint()` in `demo-app/web/index.php`) and `Checkout::subscriptionWebhookHandler()` 
(see `subscriptionWebhookEndpoint()` in `demo-app/web/index.php`) to handle the events. Each of these helper methods returns a specialised 
result class which gives you information about the event, and a pre-filled HTTP result code and JSON string to return to Stripe.

### Stripe Customer Portal

The Customer Portal is a configurable Stripe hosted control panel that allows existing customers to make changes to 
payment methods for subscriptions and change subscription plans. The specific features that are enabled are configured
in the Stripe Dashboard. The `Checkout` class has a function, `createBillingPortalSessionUrl()` (see `redirectToCustomerPortal()` in 
`demo-app/web/index.php`) for generating a short-lived link enabling a user access to the Portal. The user must be identified by 
a Stripe Customer ID, as it's not possible to look up a Customer using their email address. You can create a page in your
front-end that generates this link and then redirects the user to it. It's advisable to link to this redirect page with a POST form
and use a 302 redirect so that the redirect isn't cached.

## Stripe configuration notes

- To use stripe tax you need to enable it in the dashboard: 
  - https://dashboard.stripe.com/test/settings/tax
- To use the customer portal, it needs configuring in the dashboard:
  - https://dashboard.stripe.com/settings/billing/portal
- Limiting users to one subscription. by default you can purchase multiple, but this can be changed.
  - https://docs.stripe.com/payments/checkout/limit-subscriptions
      - note that you need to enable the no-code customer portal if you're not redirecting the user to a handling
        page on your site:
        https://dashboard.stripe.com/settings/billing/portal
        however this requires them to receive an email from stripe to access the portal which isn't good, so it's better to
        use the redirect to your site and generate a portal link for them if that's what you want to offer.
      - note that you can only globally enable or disable multiple subscriptions. if you need more fine grained control then
        it needs to be handled manually.
- You can simulate subscription processes:
  - https://docs.stripe.com/billing/testing/test-clocks/simulate-subscriptions