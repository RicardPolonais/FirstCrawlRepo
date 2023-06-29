<?
include "fake_database.php";

if(!$_SESSION["logged"]) {
   header("Location: index.php"); 
	exit;
}

?>
<p>
<a href="mk_sitemap.php?no_cron">Make sitemap</a>
</p>

<?
@include "sitemap.html";

?>