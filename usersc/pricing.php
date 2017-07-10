<?php

if (file_exists("install/index.php")) {
    //perform redirect if installer files exist
    //this if{} block may be deleted once installed
    header("Location: install/index.php");
}

require_once '../users/init.php';
require_once $abs_us_root . $us_url_root . '../users/includes/header.php';
require_once $abs_us_root . $us_url_root . '../users/includes/navigation.php';
require_once $abs_us_root . $us_url_root . '../usersc/includes/body_top.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading"><h2>Pricing</h2></div>
            <div class="panel-body">
                <div align="center" style="font-size:x-large">You can put a message here.</div>
                <BR /><BR />
                <div class="col-xs-6 col-sm-6 col-md-offset-2 col-md-4 col-lg-3">

                    <!-- PRICE ITEM -->
                    <div class="panel price panel-red">
                        <div class="panel-heading  text-center">
                            <h3>STANDARD</h3>
                        </div>
                        <div class="panel-body text-center">
                            <p class="lead" style="font-size:40px"><strong><del><font size="3">$29/week</font></del><BR />$9/week</strong></p>
                        </div>
                        <ul class="list-group list-group-flush text-center">
                            <li class="list-group-item"><i class="icon-ok text-danger"></i> Unlimited Thing
                            </li>
                            <li class="list-group-item"><i class="icon-ok text-danger"></i> 20GB/week Bandwidth
                            </li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> All Things</li>
                            <li class="list-group-item"><i class="icon-ok text-danger"></i> 24/7 Support
                            </li>
                        </ul>
                        <div class="panel-footer">
                            <a class="btn btn-lg btn-block btn-danger" href="checkout.php?selected=3">BUY NOW!</a>
                        </div>
                    </div>
                    <!-- /PRICE ITEM -->
                </div>

                <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3">

                    <!-- PRICE ITEM -->
                    <div class="panel price panel-blue">
                        <div class="panel-heading arrow_box text-center">
                            <h3>PRO</h3>
                        </div>
                        <div class="panel-body text-center">
                            <p class="lead" style="font-size:40px"><strong><del><font size="3">$39/week</font></del><BR />$14/week</strong></p>
                        </div>
                        <ul class="list-group list-group-flush text-center">
                            <li class="list-group-item"><i class="icon-ok text-info"></i> Unlimited Thing</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> Unlimited Thing</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> All Things</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> 24/7 Support</li>
                        </ul>
                        <div class="panel-footer">
                            <a class="btn btn-lg btn-block btn-info" href="checkout.php?selected=4">BUY NOW!</a>
                        </div>
                    </div>
                    <!-- /PRICE ITEM -->
                </div>

                <div class="col-xs-6 col-sm-6 col-md-4 col-lg-3">

                    <!-- PRICE ITEM -->
                    <div class="panel price panel-blue">
                        <div class="panel-heading arrow_box text-center">
                            <h3>DEV</h3>
                        </div>
                        <div class="panel-body text-center">
                            <p class="lead" style="font-size:40px"><strong><del><font size="3">$69/week</font></del><BR />$19/week</strong></p>
                        </div>
                        <ul class="list-group list-group-flush text-center">
                            <li class="list-group-item"><i class="icon-ok text-info"></i> Unlimited Things</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> All Things</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> Custom Things</li>
                            <li class="list-group-item"><i class="icon-ok text-info"></i> Developer API</li>
                        </ul>
                        <div class="panel-footer">
                            <a class="btn btn-lg btn-block btn-info" href="checkout.php?selected=5">BUY NOW!</a>
                        </div>
                    </div>
                    <!-- /PRICE ITEM -->
                </div>

            </div>
        </div><!-- /panel -->
    </div><!-- /.col -->
</div><!-- /.row -->

<div class="row">
    <div class="col-xs-12">
        <div class="well"><p>If you're ready to unleash the awesome power of stuff, <a
                        href="/users/join.php">Register</a> for an account to get started.</p>
            <p>Visit our <a href="pricing.php">Pricing</a> page to have a look at our awesome prices. We also have an
                <a
                        href="#">F.A.Q.</a>
        </div>
    </div><!-- /.col -->
</div><!-- /.row -->

</div> <!-- /container -->

</div> <!-- /#page-wrapper -->

<!-- footers -->
<?php require_once $abs_us_root . $us_url_root . 'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->


<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; // currently just the closing /body and /html ?>
