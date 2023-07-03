<?
include "login_database.php";
include "crawler.php";

// Start at the website’s root URL (i.e. home page) *
// *it makes no sense to crawl empty page, so we will crawl some test urls

$startURL = 'http://www.kslomianki.pl';
//$startURL = 'https://wp-media.me';
//$startURL = 'https://onet.pl/';
$startURL = 'https://osir-zoliborz.waw.pl/plywalnia/grafik-zajec/';

$siteMapper = new siteMapper($startURL);
$siteMapper->run();
exit;
if( isset($_GET["no_cron"]) ){
	//just go back
	header("Location: index.php"); 
	
}else{
	echo "Cronjob done";
}
exit;
?>