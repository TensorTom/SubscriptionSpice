<?php

require_once '/users/init.php';
require_once '/braintree/lib/Braintree.php';

Braintree_Configuration::environment('sandbox');
Braintree_Configuration::merchantId('');
Braintree_Configuration::publicKey('');
Braintree_Configuration::privateKey('');

require_once $abs_us_root.$us_url_root.'users/includes/header.php';
require_once $abs_us_root.$us_url_root.'users/includes/navigation.php';

$db = DB::getInstance();

if(isset($_GET['selected'])) {
    $selected = $_GET['selected'];
}

?>

<div id="page-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><h2>Subscribe</h2></div>
                    <div class="panel-body"><p align="center">
                            <?php if($user->isLoggedIn()){$uid = $user->data()->id;?>
                                <img src="images/PaymentMethods.png" width="345" height="44">
                        </p>
                        <label for="firstName">First Name</label>
                        <input type="text" class="form-control" id="firstName" placeholder="First Name">
                        <BR />
                        <label for="lastName">Last Name</label>
                        <input type="text" class="form-control" id="lastName" aria-describedby="nameHelp" placeholder="Last Name">
                        <small id="nameHelp" class="form-text text-muted">Your name associated with your billing method.</small>
                        <BR/>
                        <label for="lastName">Zip Code</label>
                        <input type="number" class="form-control" id="zip" placeholder="Billing Zip Code">
                            <BR />
                            <label for="productSelect">Product</label>
                            <select class="form-control" id="productSelect">
                                <?php
                                $productsQ = $db->query("SELECT * FROM products");
                                if($productsQ->count() > 0)
                                {
                                    $productsO = $productsQ->results();
                                    foreach($productsO as $product)
                                    {
                                        if(isset($selected) && $selected == $product->id) {
                                            $selectedHtml = ' selected=selected';
                                        }else
                                        {
                                            $selectedHtml = '';
                                        }
                                        echo '<option value="'.$product->id.'"'.$selectedHtml.'>'.$product->name.' - $'.$product->price.'/'.$product->frequency.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        <BR />
                        <form id="checkout" onsubmit="return false;" class="custom form-search" style="background:none !important;border:0px;padding:0px">
                            <div id="dropin"></div>
                            <div align="center"><button id="braintree-button" type="submit" class="btn btn-info" role="button">Subscribe</button></div>
                        </form>
                            <?php }else{?>
                                In order to subscribe, you must first have an account. Please log in or sign up...
                                <BR />
                                <BR />
                                <a class="btn btn-warning" href="/users/login.php" role="button">Log In &raquo;</a>&nbsp;
                                <a class="btn btn-info" href="/users/join.php" role="button">Sign Up &raquo;</a>
                            <?php } ?>
                    </div>
                </div><!-- /panel -->
            </div><!-- /.col -->
        </div><!-- /.row -->

        <div class="row">
            <div class="col-xs-12">
                <div class="well"><p>If you're ready to unleash the awesome power of stuff, <a href="/users/join.php">Register</a> for an account to get started.</p>
                    <p>Visit our <a href="/pricing.php">Pricing</a> page to have a look at our awesome prices. We also have an <a href="/faq.php">F.A.Q.</a>
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->

    </div> <!-- /container -->

</div> <!-- /#page-wrapper -->

<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->
<script src="https://js.braintreegateway.com/v2/braintree.js"></script>
<script>
    braintree.setup("<?php echo($clientToken = Braintree_ClientToken::generate()); ?>","dropin", {
        container: "dropin",
        onPaymentMethodReceived: function (obj) {
            $('#checkout').append("<input type='hidden' name='payment-method-nonce' id='payment-method-nonce' value='" + obj.nonce + "'>");
            //alert(obj.nonce);
            $('#checkout').submit();
            var nonce = document.getElementById('payment-method-nonce').value;
            secondPageSubmit(nonce);
        }
    });
</script>

<script type="text/javascript">
    // finish it off and move onto confirmation
    function secondPageSubmit(nonce)
    {
        /*var FirstName = document.getElementById("FirstName").value;
        var LastName = document.getElementById("LastName").value;
        var BillingZip = document.getElementById("BillingZip").value;
        var MobilePhone = document.getElementById("MobilePhone").value;
        var Email = document.getElementById("Email").value;
        var Age = document.getElementById("Age").value;
        var Gender = document.getElementById("Gender").value;
        var Build = document.getElementById("Build").value;*/
        var product = document.getElementById("productSelect").value;
        var params = "action=addSubscription&product="+product+"&nonce="+nonce;
        //var params = "action=addSubscription&product="+product+"&FirstName="+FirstName+"&LastName="+LastName+"&BillingZip="+BillingZip+"&MobilePhone="+MobilePhone+"&Email="+Email+"&Age="+Age+"&Gender="+Gender+"&Build="+Build+"&nonce="+nonce;
        var xhttp = new XMLHttpRequest();
        xhttp.open("POST", "api/", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.setRequestHeader("Content-length", params.length);
        xhttp.setRequestHeader("Connection", "close");

        xhttp.onreadystatechange = function()
        {
            if (xhttp.readyState == 4 && xhttp.status == 200)
            {
                //alert(xhttp.responseText);
                if(xhttp.responseText.length > 0)
                {
                    var ResponseAr = JSON.parse(xhttp.responseText);
                    var i;
                    for(i = 0; i < ResponseAr.length; i++)
                    {
                        if(ResponseAr[i].Error)
                        {
                            alert("Error: "+ResponseAr[i].Error);
                        }else if(ResponseAr[i].Success)
                        {
                            var URLString = ResponseAr[i].Success;
                            window.location = "confirmation.php";
                        }else
                        {
                            alert("There was trouble connecting to our site. Please check your Internet connection and try again.");
                        }
                    }
                }
            }
        };
        xhttp.send(params);
    }
</script>
<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; // currently just the closing /body and /html ?>

