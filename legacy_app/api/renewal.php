<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
// Only show to logged-in users of expired org

echo "<h2>Your Organization's Subscription Has Expired</h2>";
echo "<p>Please renew your subscription to regain access.</p>";
echo "<a href='/payment.php'>Renew Now</a>";
?>