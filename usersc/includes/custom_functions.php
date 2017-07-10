<?php
/*
UserSpice 4
An Open Source PHP User Management System
by the UserSpice Team at http://UserSpice.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Put your custom functions in this file and they will be automatically included.

//bold("<br><br>custom helpers included");

require_once $abs_us_root.'/vendor/autoload.php';

function sendMail($to,$firstName,$lastName,$subject,$body,$bodyText)
{
    $transport = Swift_SendmailTransport::newInstance();
    $mailer = Swift_Mailer::newInstance($transport);
    $message = Swift_Message::newInstance($subject)
    //->setSubject($subject)
    ->setFrom(array("noreply@site.com" => "Site"))
    ->setTo(array($to => "$firstName $lastName"))
    ->setBody($body, 'text/html')
    ->addPart($bodyText);
    return $mailer->send($message);
}

function userHasPermission($userID,$permissionID)
{
    $permissionsAr = fetchUserPermissions($userID);
    //if($permissions[0])
    foreach($permissionsAr as $perm)
    {
        if($perm->permission_id == $permissionID)
        {
            return TRUE;
        }
    }
    return FALSE;
}

function paramsToArray($parameters)
{
    $parametersAr1 = explode('&',$parameters);
    $parametersResultAr = array();
    foreach ($parametersAr1 as $params1)
    {
        $parametersAr2 = explode('=',$params1);
        $parametersResultAr[$parametersAr2[0]] = $parametersAr2[1];
    }
    return $parametersResultAr;
}

function returnError($errorMsg)
{
    $responseAr['error'] = TRUE;
    $responseAr['errorMessage'] = $errorMsg;
    echo json_encode($responseAr);
    die();
}

function requestCheck($expectedAr)
{
    if(isset($_GET) && isset($_POST))
    {
        $requestAr = array_replace_recursive($_GET, $_POST);
    }elseif(isset($_GET)){
        $requestAr = $_GET;
    }elseif(isset($_POST)){
        $requestAr = $_POST;
    }else{
        $requestAr = array();
    }
    $diffAr = array_diff_key(array_flip($expectedAr),$requestAr);
    if(count($diffAr) > 0)
    {
        returnError("Missing variables: ".implode(',',array_flip($diffAr)).".");
    }else {
        return $requestAr;
    }
}

function httpPost($url,$params)
{
    $postData = '';
    //create name value pairs seperated by &
    foreach($params as $k => $v)
    {
        $postData .= $k . '='.$v.'&';
    }
    $postData = rtrim($postData, '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Site Server Manager");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $output=curl_exec($ch);

    curl_close($ch);
    return $output;

}

function genCryptSalt()
{
    $str = chunk_split(bin2hex(random_bytes(16)),2,"\\x");
    return "\\x" . substr($str,0,-2);
}

function encrypt($message, $key)
{
    $nonce = \Sodium\randombytes_buf(\Sodium\CRYPTO_SECRETBOX_NONCEBYTES);

    $cipher = base64_encode($nonce.\Sodium\crypto_secretbox($message, $nonce, $key));
    \Sodium\memzero($message);
    \Sodium\memzero($key);
    return $cipher;
}

function decrypt($encrypted, $key)
{
    $decoded = base64_decode($encrypted);
    $nonce = mb_substr($decoded, 0, \Sodium\CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, \Sodium\CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

    $plain = \Sodium\crypto_secretbox_open(
        $ciphertext,
        $nonce,
        $key
    );
    \Sodium\memzero($ciphertext);
    \Sodium\memzero($key);
    return $plain;
}

function getKeyFromPassword($password, $randomSalt, $keysize = 32)
{
    return hash_pbkdf2('sha256', $password, $randomSalt, 100000, $keysize, true);
}

function processPayment($planId,$paymentAmount,$nonce)
{
    // This function was for Braintree and is deprecated in favor of Stripe
    $paymentResult = Braintree_Transaction::sale([
        'amount' => "$paymentAmount",
        'paymentMethodNonce' => $nonce,
        'options' => [
            //'submitForSettlement' => true
            'store_in_vault_on_success' => true
        ]
    ]);

    $paymentToken = $paymentResult->transaction->credit_card_details->token;

    $subscriptionResult = Braintree_Subscription::create([
        'payment_method_token' => $paymentToken,
        'plan_id' => $planId
    ]);

    if($paymentResult->success && $subscriptionResult->success)
    {
        return TRUE;
    }else
    {
        return FALSE;
    }
}

function stripeCreateCustomer($stripeAPIkey,$username,$email,$firstName,$lastName,$db)
{
    // Create a stripe customer and add them to the DB
    \Stripe\Stripe::setApiKey($stripeAPIkey);
    try {
        return \Stripe\Customer::create([
            "email" => $email,
            "metadata" => ["username" => $username, "firstName" => $firstName, "lastName" => $lastName]
        ]);
    } catch(Exception $e) {
        return false;
    }
}

function logBaseSubscription($db,$username,$email)
{
    $userQ = $db->query("SELECT `id` FROM `users` WHERE `username` = ? AND `email` = ?",[$username,$email]);
    if($userQ->count() > 0)
    {
        $userO = $userQ->results()[0];
        // $customer->created // for something
        $db->query("INSERT INTO subscriptions SET username = ?, user_id = ?, product = 0, price = 0.00, billing_day = 0, active = 0", [$username, $userO->id]);
        return true;
    }else{
        return false;
    }
}

function stripeCreateSubscription($stripeAPIkey,$stripeToken,$db,$product,$currentUser,$coupon = false)
{
    // Create or change a subscription
    \Stripe\Stripe::setApiKey($stripeAPIkey);

    // Check that product exists and get the row
    $productQ = $db->query("SELECT * FROM products WHERE id = ? AND `active` = 1 LIMIT 1",[$product]);
    if($productQ->count() > 0)
    {
        $productO = $productQ->results()[0];

        $successMailSubject = "Thanks for Subscribing";
        $successMailBody = "Hi ".$currentUser->fname.",<BR><BR>\n\n"."
                    Thank you for subscribing to Site. You're now subscribed to our ".$productO->name." product. You can manage your whatever
                    via your <a href=\"https://www.site.com/users/product_manager.php\">Product Manager</a>. You can also view your billing history at
                    the <a href=\"https://www.site.com/users/billing.php\">Billing</a> page. Need help? Feel free to submit a support ticket at our
                    <a href=\"https://www.site.com/help-desk/\">Help Desk</a> or start a Live Help chat from any page of our site.<BR><BR>\n\n
                    Best Regards,<BR><BR>\n\n
                    - Site<BR>\n
                    <a href=\"https://www.site.com\">www.site.com</a>";
        $successMailBodyText = "Hi ".$currentUser->fname.",\n\n"."
                    Thank you for subscribing to Site. You're now subscribed to our ".$productO->name." product. You can manage your proxy pools
                    via your Proxy Manager: https://www.site.com/users/proxy_manager.php . You can also view your billing history at
                    the billing page: https://www.site.com/users/billing.php . Need help? Feel free to submit a support ticket at our
                    Help Desk: https://www.site.com/help-desk/ or start a Live Help chat from any page of our site.\n\n
                    Best Regards,\n\n
                    - Site\n
                    www.site.com";
        $failedMailSubject = "Problem Subscribing - Error: ";
        $failedMailBody = "Hi ".$currentUser->fname.",<BR><BR>\n\n"."
                    Thank you for your interest in Site. Unfortunately, there was a problem processing your subscription payment. Please check with
                    your financial institution and try again. If you're certain that your payment should have went through, please submit a ticket to our
                    <a href=\"https://www.site.com/help-desk/\">Help Desk</a> or start a Live Chat with us from any page of our site so we can help
                    you get started. We apologize for any inconvenience you're experiencing and we're here to help.
                    Best Regards,<BR><BR>\n\n
                    - Site<BR>\n
                    <a href=\"https://www.site.com\">www.site.com</a>";
        $failedMailBodyText = "Hi ".$currentUser->fname.",\n\n"."
                    Thank you for your interest in Site. Unfortunately, there was a problem processing your subscription payment. Please check with
                    your financial institution and try again. If you're certain that your payment should have went through, please submit a ticket to our
                    Help Desk: https://www.site.com/help-desk/ or start a Live Chat with us from any page of our site so we can help
                    you get started. We apologize for any inconvenience you're experiencing and we're here to help.
                    Best Regards,\n\n
                    - Site\n
                    www.site.com";

        // Get the subscription row of the user if one exists
        $subscriptionQ = $db->query("SELECT * FROM subscriptions WHERE user_id = ? LIMIT 1",[$currentUser->id]);
        if($subscriptionQ->count() > 0)
        {
            // lookup coupon and calculate cost
            if($coupon !== false) {
                $couponQ = $db->query("SELECT * FROM coupons WHERE coupon_name = ?", [$coupon]);
                if($couponQ->count() > 0)
                {
                    $couponO = $couponQ->results()[0];
                    $coupon = $couponO->coupon_name;
                    $discount = $productO->price * $couponO->percentage / 100;
                    $totalCost = $productO->price - $discount;
                }else{
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject."F1",$failedMailBody,$failedMailBodyText);
                    return false;
                }
            }
            // If user has a paid sub, set the new subscription options
            $subOptionsAr = [
                "customer" => $currentUser->stripe_cust_id,
                "plan" => $productO->plan_id,
                "source" => $stripeToken
            ];
            if($coupon !== false)
            {
                //die($coupon);
                // If the user is using a coupon, add it to sub options.
                $subOptionsAr["coupon"] = $coupon;
            }
            $subscriptionO = $subscriptionQ->results()[0];
            if($subscriptionO->product == 0 && $subscriptionO->product != $product)
            {
                // If user doesn't have a paid sub & isn't trying to buy the same thing twice, add sub
                try {
                    $subscription = \Stripe\Subscription::create($subOptionsAr);
                    $db->query("UPDATE subscriptions SET product = ?, sub_id = ?, price = ?, active = 1 WHERE user_id = ?", [$product, $subscription->id, $productO->price, $currentUser->id]);
                    // If a coupon was applied, look up coupon details and calculate final price. Add transaction details to ledger.
                    if($coupon !== false)
                    {
                            $db->query("INSERT INTO transactions SET product_id = ?, amount = ?, user_id = ?, coupon_id = ?, subscription_id = ?, status = 'complete', processor = 'stripe'",[$product, $totalCost, $currentUser->id, $couponO->id, $subscription->id]);
                    }else{
                        $totalCost = $productO->price;
                        $db->query("INSERT INTO transactions SET product_id = ?, amount = ?, user_id = ?, subscription_id = ?, status = 'complete', processor = 'stripe'",[$product, $totalCost, $currentUser->id, $subscription->id]);
                    }
                    addPermission([$productO->permission],$currentUser->id);
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$successMailSubject,$successMailBody,$successMailBodyText);
                    return true;
                } catch (Exception $e){
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject."F2",$failedMailBody,$failedMailBodyText);
                    return $e;
                }
            }elseif($subscriptionO->product > 0 && $subscriptionO->product != $product){
                // If user is changing plan, update subscription
                $productOldQ = $db->query("SELECT `permission` FROM products WHERE `id` = ? AND `active` = 1 LIMIT 1",[$subscriptionO->product]);
                $productOldO = $productOldQ->results()[0];
                $subscriptionNew = \Stripe\Subscription::retrieve($subscriptionO->sub_id);
                try {
                    // If a coupon was applied, look up coupon details and calculate final price. Add transaction details to ledger.
                    if($coupon !== false) {
                        //$subscriptionNew->coupon = $coupon;
                    }
                    $subscriptionNew->plan = $productO->plan_id;
                    $subscriptionNew->save();
                    $db->query("UPDATE subscriptions SET product = ?, price = ? WHERE sub_id = ?", [$product, $productO->price, $subscriptionO->sub_id]);
                    if($coupon !== false)
                    {
                        $db->query("INSERT INTO transactions SET product_id = ?, amount = ?, user_id = ?, coupon_id = ?, subscription_id = ?, status = 'complete', processor = 'stripe'",[$product, $totalCost, $currentUser->id, $couponO->id, $subscriptionNew->id]);
                    }else{
                        $totalCost = $productO->price;
                        $db->query("INSERT INTO transactions SET product_id = ?, amount = ?, user_id = ?, subscription_id = ?, status = 'complete', processor = 'stripe'",[$product, $totalCost, $currentUser->id, $subscriptionNew->id]);
                    }
                    removePermission([$productOldO->permission],$currentUser->id);
                    addPermission([$productO->permission],$currentUser->id);
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$successMailSubject,$successMailBody,$successMailBodyText);
                    return true;
                } catch (Exception $e){
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject."F3",$failedMailBody,$failedMailBodyText);
                    return $e;
                }
            }else{
                // Unknown subscription situation returns false
                //returnError("invalid_product_request | Please report the following error code: ". $subscriptionO->product. " | ". $product);
                sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject."F4",$failedMailBody,$failedMailBodyText);
                return false;
            }
        }else{
            // If subscription doesn't exist, return false
            //returnError("invalid_subscription");
            sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject."F5",$failedMailBody,$failedMailBodyText);
            return false;
        }
    }else{
        // If product doesn't exist, return false
        //returnError("product_not_found");
        return false;
    }
}

function stripeRemoveSubscription()
{

}

function stripeChangeSubscription()
{

}

function getBetween($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
