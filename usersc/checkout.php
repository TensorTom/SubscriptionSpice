<?php

require_once '/users/init.php';

require_once $abs_us_root.$us_url_root.'users/includes/header.php';
require_once $abs_us_root.$us_url_root.'users/includes/navigation.php';

$db = DB::getInstance();

if(isset($_GET['selected'])) {
    $selected = $_GET['selected'];
    $productQ = $db->query("SELECT * FROM products WHERE id = ? AND `active` = 1",[$selected]);
    if($productQ->count() > 0)
    {
        $productSpec = true;
    }else{
        $productSpec = false;
    }
}else{
    $productSpec = false;
}
if(!$productSpec)
{
    $productQ = $db->query("SELECT * FROM products WHERE `active` = 1 LIMIT 1");
}
$productO = $productQ->results()[0];
$product = $productO->id;
$productName = $productO->name;
$productDescription = $productO->description;
$productPrice = $productO->price;
$productPriceTxt = str_replace('.','',$productPrice);
$productFrequency = $productO->frequency;

?>

<div id="page-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><h2>Subscribe</h2></div>
                    <div class="panel-body"><center>
                            <label for="productSelect">Product</label>
                            <select class="form-control" id="productSelect">
                                <?php
                                $productsQ = $db->query("SELECT * FROM products WHERE `active` = 1");
                                if($productsQ->count() > 0)
                                {
                                    $productsO = $productsQ->results();
                                    foreach($productsO as $productO)
                                    {
                                        if(isset($selected) && $selected == $productO->id) {
                                            $selectedHtml = ' selected=selected';
                                        }else
                                        {
                                            $selectedHtml = '';
                                        }
                                        echo '<option value="'.$productO->id.'"'.$selectedHtml.'>'.$productO->name.' - $'.$productO->price.'/'.$productO->frequency.'</option>';
                                    }
                                }
                                ?>
                            </select>
                            <BR />
                            <?php if($user->isLoggedIn()){$uid = $user->data()->id;?>
                        <form action="/api/?action=stripeCreateSubscription&product=<?php echo $product; ?>" method="POST">
                            <label for="coupon">Promo Code</label>
                            <input type="text" class="form-control" id="coupon" name="coupon" placeholder="Enter promo code if any">
                            <BR />
                            <!-- data-bitcoin="true" -->
                            <script
                                    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                                    data-key="<?php echo $stripePubKey; ?>"
                                    data-zip-code="true"
                                    data-billing-address="true"
                                    data-amount="<?php echo $productPriceTxt; ?>"
                                    data-name="<?php echo $productName; ?>"
                                    data-description="<?php echo $productDescription.', '.$productPrice.'/'.$productFrequency; ?>"
                                    data-image="https://www.infiniproxy.com/images/lock-icon.png"
                                    data-locale="auto">
                            </script>
                        </form>
                        <img src="images/ssl-secured.png" width="215" height="215">
                        </center>
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
                    <p>Visit our <a href="pricing.php">Pricing</a> page to have a look at our awesome prices. We also have an <a href="/faq.php">F.A.Q.</a>
                </div>
            </div><!-- /.col -->
        </div><!-- /.row -->

    </div> <!-- /container -->

</div> <!-- /#page-wrapper -->

<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->
<script>
    $("#coupon").on('input',function(){
        // change keydown paste
        $.ajax({
            method:'POST',
            url:'/api/',
            dataType: 'json',
            data: {"action": "verifyCoupon","coupon": $("#coupon").val()},
            success:function(couponData)
            {
                if(couponData.error == false)
                {
                    var couponCode = $("#coupon").val();
                    if(couponData.couponValid == true || couponCode.length == 0)
                    {
                        $(".stripe-button-el").prop( "disabled", false );
                    }else {
                        $(".stripe-button-el").prop( "disabled", true );
                    }
                }else{
                    alert("Error validating promo code.");
                }
            }
        });
    });
</script>

<script type="text/javascript">
    // This is deprecated Braintree stuff
    /*var YourParam="sunshine";

    $(document).ready(function() {
        $("#productSelect").change(function(){
            if ($(this).val()!='') {
                window.location.href="/checkout.php?selected="+YourParam;
            }
        });
    });*/
    $("#productSelect").change(function () {
        document.location.href = 'checkout.php?selected=' + $(this).val();
    });
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
                            alert("There was trouble connecting to Site. Please check your Internet connection and try again.");
                        }
                    }
                }
            }
        };
        xhttp.send(params);
    }
</script>
<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; // currently just the closing /body and /html ?>

