<?php

require_once '/users/init.php';

require_once $abs_us_root.$us_url_root.'users/includes/header.php';
require_once $abs_us_root.$us_url_root.'users/includes/navigation.php';
?>

<div id="page-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><h2>Oops</h2></div>
                    <div class="panel-body">
                        There was a problem processing your subscription. You can <a href="checkout.php">try again</a> or visit our <a href="https://www.site.com/help-desk/">Help Desk</a> if you're experiencing a problem.
                    </div>
                </div><!-- /panel -->
            </div><!-- /.col -->
        </div><!-- /.row -->

    </div> <!-- /container -->

</div> <!-- /#page-wrapper -->

<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->

<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; // currently just the closing /body and /html ?>
