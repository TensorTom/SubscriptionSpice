<?php

$responseAr = array();

require_once('/vendor/autoload.php');
require_once('/users/init.php');

//require_once('../braintree/lib/Braintree.php');

if(!isset($_REQUEST['apiKey'])) {
    $currentUser = $user->data();
}

if(isset($_REQUEST['action']) )
{
    $action = $_REQUEST['action'];
    $responseAr['error'] = false;
    $db = DB::getInstance();
}else{ returnError("missing_action"); }

if(isset($currentUser->id)) {
    $loggedIn = true;
    if (userHasPermission($currentUser->id, 4) || userHasPermission($currentUser->id, 5) || userHasPermission($currentUser->id, 6)) {
        $subscription = true;
    }else {
        $subscription = false;
    }
}elseif(isset($_REQUEST['apiKey'])) {
    $apiKey = $_REQUEST['apiKey'];
    $apiKeyQ = $db->query("SELECT `id` from `users` WHERE `api_key` = ?", [$apiKey]);
    if($apiKeyQ->count() > 0)
    {
        $apiKeyO = $apiKeyQ->results()[0];
        if(userHasPermission($apiKeyO->id, 6)) {
            $subscription = true;
            $currentUser = "";
            $currentUser->id = $apiKeyO->id;
            //echo $currentUser-id."sfsdsdfs";
        }else{
            $subscription = false;
        }
    }else{
        $subscription = false;
    }
}else{
    $loggedIn = false;
    $subscription = false;
}

switch($action)
{
    case "getBillingStats":
        if(!$subscription){ returnError("no_subscription"); }
        $subscriptionO = $db->get('subscriptions',['user_id','=',$currentUser->id]);
        if($subscriptionO->count() > 0)
        {
            $responseAr['subscriber'] = true;
            $subscriptionAr = $subscriptionO->results();
            if($subscriptionAr[0]->active == 1)
            {
                $responseAr['active'] = true;
                $responseAr['product'] = $subscriptionAr[0]->product;
                $responseAr['price'] = $subscriptionAr[0]->price;
                $responseAr['billingDay'] = $subscriptionAr[0]->billing_day;
            }else{
                $responseAr['active'] = false;
            }
        }else {
            $responseAr['subscriber'] = false;
        }
        break;
    case "verifyCoupon":
        $requestAr = requestCheck(['coupon']);
        $couponQ = $db->query("SELECT * FROM coupons WHERE coupon_name = ? LIMIT 1",[$requestAr['coupon']]);
        if($couponQ->count() > 0)
        {
            $responseAr['couponValid'] = true;
        }else{
            $responseAr['couponValid'] = false;
        }
        break;
    case "stripeCreateCustomer":
        if($loggedIn == false){ returnError("not_logged_in"); }
            $customer = stripeCreateCustomer($test_secret,$currentUser->id,$currentUser->username,$currentUser->email,$firstName,$lastName,$db);
        if($customer)
        {
            $responseAr['customer'] = true;
        }else{
            $responseAr['customer'] = false;
        }
        break;
    case "stripeCreateSubscription":
        if($loggedIn == false){ returnError("not_logged_in"); }
        $requestAr = requestCheck(['product','stripeToken']);
        \Stripe\Stripe::setApiKey($stripeAPIkey);
        if(isset($_REQUEST['coupon']) && strlen($_REQUEST['coupon']) > 0)
        {
            $coupon = $_REQUEST['coupon'];
        }else{
            $coupon = false;
        }

        $productQ = $db->query("SELECT price, plan_id FROM products WHERE id = ? AND `active` = 1 LIMIT 1",[$requestAr['product']]);
        if($productQ->count() > 0)
        {
            $productO = $productQ->results()[0];
            $price = $productO->price;
            $planId = $productO->plan_id;
            $subscription = stripeCreateSubscription($stripeAPIkey, $requestAr['stripeToken'], $db, $requestAr['product'], $currentUser, $coupon);
            if($subscription === true)
            {
                header('Location: https://www.site.com/thank-you.php');
                die();
            }else{
                header('Location: https://www.site.com/oops.php');
                //var_dump($subscription);
                die();
            }
        }else{
            returnError("invalid_product");
        }
        break;
    case "stripeHook":
        // Stripe hits this with transactional data
        /*  invoice.payment_failed // notify user
         *  customer.subscription.deleted // remove user
         *  charge.failed // notify user
         *  invoice.payment_succeeded // notify user
         *  invoice.sent // notify user
         */
        \Stripe\Stripe::setApiKey($stripeAPIkey);
        // Retrieve the request's body and parse it as JSON
        $input = @file_get_contents("php://input");
        $eventO = json_decode($input);
        try {
            $eventCheck = \Stripe\Event::retrieve($eventO->id);
            $responseAr['eventCheck'] = $eventCheck;
            $responseAr['webhook'] = $eventO;
            if(isset($eventO)) {
                // If there's an event (There always should be) grab customer
                $customer = \Stripe\Customer::retrieve($eventO->data->object->customer);
                $email = $customer->email;
                if($eventO->type == "invoice.payment_failed" || $eventO->type == "charge.failed")
                {
                    // invoice payment failed so alert user
                    // Convert amount owed to $ for sending to user
                    $amountOwed = sprintf('$%0.2f', $event->data->object->amount_due / 100.0);
                    $failedMailSubject = "Subscription Payment Failed";
                    $failedMailBody = "Hi ".$currentUser->fname.",<BR><BR>\n\n"."
                        Thank you for your loyalty as a customer. Unfortunately, there was a problem processing your subscription payment. Please check with
                        your financial institution and update your payment method if necessary. If you're certain that your payment should have went through,
                        please submit a ticket to our <a href=\"https://www.site.com/help-desk/\">Help Desk</a> or start a Live Chat with us from any
                        page of our site so we can help you. We apologize for any inconvenience you're experiencing and we're here to help.
                        Best Regards,<BR><BR>\n\n
                        - Site<BR>\n
                        <a href=\"https://www.site.com\">www.site.com</a>";
                    $failedMailBodyText = "Hi ".$currentUser->fname.",\n\n"."
                        Thank you for your loyalty as a customer. Unfortunately, there was a problem processing your subscription payment. Please check with
                        your financial institution and update your payment method if necessary. If you're certain that your payment should have went through,
                        please submit a ticket to our Help Desk: https://www.site.com/help-desk/ or start a Live Chat with us from any
                        page of our site so we can help you. We apologize for any inconvenience you're experiencing and we're here to help.
                        Best Regards,\n\n
                        - Site\n
                        www.site.com";
                    sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject,$failedMailBody,$failedMailBodyText);
                }elseif($eventO->type == "customer.subscription.deleted") {
                    // suspend user for too many failed payments
                    $subscriptionQ = $db->query("SELECT * FROM subscriptions WHERE email = ?",[$email]);
                    if($subscriptionQ->count() > 0)
                    {
                        $db->query("UPDATE subscriptions SET active = 0, product = 0 WHERE user_id = ?",[$currentUser->id]);
                        removePermission([4,5],$currentUser->id);
                        $failedMailSubject = "Account Suspension Notice";
                        $failedMailBody = "Hi ".$currentUser->fname.",<BR><BR>\n\n"."
                            Thank you for your loyalty as a customer. Unfortunately, we haven't been able to process your subscription payment. Your account has
                            been automatically suspended due to non-payment. Please check with your financial institution and update your payment method if necessary.
                            If you're certain that your payment should have went through, please submit a ticket to our <a href=\"https://www.site.com/help-desk/\">Help Desk</a>
                            or start a Live Chat with us from any page of our site so we can help you. We apologize for any inconvenience you're experiencing and we're here to help.
                            Best Regards,<BR><BR>\n\n
                            - Site<BR>\n
                            <a href=\"https://www.site.com\">www.site.com</a>";
                        $failedMailBodyText = "Hi ".$currentUser->fname.",\n\n"."
                            Thank you for your loyalty as a customer. Unfortunately, we haven't been able to process your subscription payment. Your account has
                            been automatically suspended due to non-payment. Please check with your financial institution and update your payment method if necessary.
                            If you're certain that your payment should have went through, please submit a ticket to our Help Desk: https://www.site.com/help-desk/
                            or start a Live Chat with us from any page of our site so we can help you. We apologize for any inconvenience you're experiencing and we're here to help.
                            Best Regards,\n\n
                            - Site\n
                            www.siote.com";
                        sendMail($currentUser->email,$currentUser->fname,$currentUser->lname,$failedMailSubject,$failedMailBody,$failedMailBodyText);
                    }
                }else{
                    $responseAr["response"] = "event_not_handled";
                }
            }else {
                // If there's no event (There always should be)
                returnError("invalid_event");
            }
        }catch (Exception $e){
            // Naughty user trying to trick us?
            returnError("invalid_stripe_event");
        }
        //file_put_contents("webhook.txt",$event_json);
        // Respond to Stripe
        //http_response_code(200); // PHP 5.4 or greater
        break;
    case "cancelSubscription":
        if(!$subscription){ returnError("no_subscription"); }
        $subscriptionQ = $db->query("SELECT id FROM subscriptions WHERE user_id = ?",[$currentUser->id]);
        if($subscriptionQ->count() > 0)
        {
            removePermission([4,5],$currentUser->id);
            $responseAr['response'] = "subscription_removed";
        }else{
            returnError("subscription_not_found");
        }
        break;
    default:
        returnError("invalid_action");
        break;
}

echo json_encode($responseAr);
