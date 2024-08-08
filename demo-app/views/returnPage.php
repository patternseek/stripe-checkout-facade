<!DOCTYPE html>
<html>
<head>
    <title>
        <?= 
            /** @var \PatternSeek\StripeCheckoutFacade\ValueTypes\CheckoutSessionInformation $sessionInfo */
            $sessionInfo->readyForFulfilment()?'Thanks for your purchase!':'Oh no!'
        ?></title>
    <link rel="stylesheet" href="style.css">

</head>
<body>

<?=$sessionInfo->readyForFulfilment()?"Checkout succeeded!":"Checkout failed!"?>
<br>
<?php
if( $sessionInfo->readyForFulfilment() ){
?>
    <form method="POST" action="/?route=redirectToCustomerPortal&customerId=<?=$sessionInfo->customer?>">
        <button type="submit">Manage billing</button>
    </form>
<?php
}
?>
<br>
<pre>
<?php
print_r($sessionInfo);
?>
</pre>
</body>
</html>