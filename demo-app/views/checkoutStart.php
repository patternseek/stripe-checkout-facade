<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Accept a payment</title>
    <meta name="description" content="A demo of a payment on Stripe" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="style.css" />
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<!-- Display a payment form -->
<div id="checkout">
    <!-- Checkout will insert the payment form here -->
</div>

<script>
  // This is your test public API key.
  const stripe = Stripe("<?= $apiPublicKey ?>");

  initialize();

  // Create a Checkout Session
  async function initialize() {

    const checkout = await stripe.initEmbeddedCheckout({
      // Stripe's example uses and async call to fetch this but that seems
      // unnecessary, depending on your payment flow.
      clientSecret: "<?= $sessionClientSecret ?>"
    });

    // Mount Checkout
    checkout.mount('#checkout');
  }
</script>

</body>
</html>