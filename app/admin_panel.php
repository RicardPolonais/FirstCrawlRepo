<?php
include "login_database.php";

if(!$_SESSION["logged"]) {
   header("Location: index.php"); 
	exit;
}

?>
<p>
<a href="mk_sitemap.php?no_cron">Make new sitemap</a>
</p>
<p>
<a href="logout.php">Logout</a>
</p>

<?php
// Display the results on the admin page.
// When the admin requests to view the results, pull the results from storage and display them on the admin page
@include "results/crawl_database.php";
if($lastCrawlTime) echo "<p>Last crawl: ".date("Y-m-d H:i:s", $lastCrawlTime)."</p>";

@include "results/sitemap.html";