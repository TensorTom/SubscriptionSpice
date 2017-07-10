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
?>
<?php
require_once 'init.php';
require_once $abs_us_root.$us_url_root.'users/includes/header.php';
require_once $abs_us_root.$us_url_root.'users/includes/navigation.php';

$db = DB::getInstance();
$currentUser = $user->data();
?>

<?php if (!securePage($_SERVER['PHP_SELF'])){die();} ?>
<?php
//PHP Goes Here!
?>
<div id="page-wrapper">
    <div class="container">
        <h1 class="text-center">Billing History</h1>
        <div class="row">
            <div class="col-sm-12">

                <!-- Pools Panel -->
                <div class="col-xs-6 col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Some Usage</strong></div>
                        <div class="panel-body text-center"><div class="huge" id="someCount"> <i class='fa fa-spinner fa-1x'></i> </div></div>
                        <div class="panel-footer">
                            <span class="pull-left"></span>
                            <span class="pull-right"><a href="some_manager.php">Manage</a>&nbsp;&nbsp;<i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div> <!-- /panel-footer -->
                    </div><!-- /panel -->
                </div> <!-- /.col -->

                <!-- Some Panel -->
                <div class="col-xs-6 col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Some Usage</strong></div>
                        <div class="panel-body text-center"><div class="huge" id="someCount"> <i class='fa fa-spinner fa-1x'></i> </div></div>
                        <div class="panel-footer">
                            <span class="pull-left">&nbsp;<!--<a href="#">Manage</a>--></span>
                            <span class="pull-right"><!--<i class="fa fa-arrow-circle-right"></i>--></span>
                            <div class="clearfix"></div>
                        </div> <!-- /panel-footer -->
                    </div><!-- /panel -->
                </div> <!-- /.col -->

                <!-- Some Panel -->
                <div class="col-xs-6 col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Some Usage</strong></div>
                        <div class="panel-body text-center"><div class="huge" id="someUsage"> <i class='fa fa-spinner fa-1x'></i> </div></div>
                        <div class="panel-footer">
                            <span class="pull-left"><!--<a href="some_usage.php">View</a>--></span>
                            <span class="pull-right"><!--<i class="fa fa-arrow-circle-right"></i>-->&nbsp;</span>
                            <div class="clearfix"></div>
                        </div> <!-- /panel-footer -->
                    </div><!-- /panel -->
                </div> <!-- /.col -->

                <!-- Subscriptions Panel -->
                <div class="col-xs-6 col-md-3">
                    <div class="panel panel-default">
                        <div class="panel-heading"><strong>Your Subscription</strong></div>
                        <div class="panel-body text-center"><div class="huge"> <i class='fa fa-spinner fa-1x' id="subscriptionStatus"></i></div></div>
                        <div class="panel-footer">
                            <span class="pull-left">&nbsp;</span>
                            <span class="pull-right"></span>
                            <div class="clearfix"></div>
                        </div> <!-- /panel-footer -->
                    </div><!-- /panel -->
                </div> <!-- /.col -->

            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Payments</strong></div>
                    <div class="panel-body">
                        <p align="center">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice ID</th>
                                <th>Product</th>
                                <th>Subtotal</th>
                                <th>Total</th>
                                <th>Payment Status</th>
                                <th>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $billingQ = $db->query('SELECT * FROM transactions WHERE user_id = ?',[$currentUser->id]);
                            $rowCount = 0;
                            if($billingQ->count() > 0)
                            {
                                $billingAr = $billingQ->results();
                                //print_r($billingO);
                                //$billingAr = $billingO
                                foreach($billingAr as $key => $billingRow)
                                {
                                    $rowCount++;
                                    echo '<tr>';
                                    echo '<th scope="row">'.$rowCount.'</th>'."\n";
                                    echo '<td>100'.$billingRow->id.'</td>'."\n";
                                    $productQ = $db->query("SELECT name FROM products WHERE id = ?",[$billingRow->product_id]);
                                    if($productQ->count() > 0)
                                    {
                                        $productO = $productQ->results()[0];
                                        echo '<td>'.$productO->name.'</td>'."\n";
                                    }
                                    echo '<td>$'.$billingRow->amount.'</td>'."\n";
                                    if($billingRow->coupon_id > 0)
                                    {
                                        $couponQ = $db->query("SELECT * FROM coupons WHERE id = ? LIMIT 1",[$billingRow->coupon_id]);
                                        if($couponQ->count() > 0)
                                        {
                                            $couponO = $couponQ->results()[0];
                                            if($couponO->type == '%')
                                            {
                                                $discount = $billingRow->amount * $couponO->percentage / 100;
                                                $totalCost = $billingRow->amount - $discount;
                                                echo '<td>$'.$totalCost.'</td>'."\n";
                                            }else{
                                                $totalCost = $billingRow->amount - $couponO->amount;
                                                echo '<td>$'.$totalCost.'</td>';
                                            }
                                        }
                                    }else{
                                        echo '<td>$'.$billingRow->amount.'</td>';
                                    }
                                    echo '<td>'.$billingRow->status.'</td>'."\n";
                                    echo '<td>'.$billingRow->timestamp.'</td>'."\n";
                                    echo '</tr>';
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                        </p>
                    </div>
                </div><!-- /panel -->
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div> <!-- /.container -->
</div> <!-- /.wrapper -->


<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->


<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; // currently just the closing /body and /html ?>
