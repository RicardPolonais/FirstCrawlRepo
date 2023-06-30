<?
include "fake_database.php";
include "crawler.php";

$startURL = 'http://www.kslomianki.pl';

// $startURL = 'https://wp-media.me';
 //$startURL = 'https://onet.pl/';

//$startURL = 'https://osir-zoliborz.waw.pl/plywalnia/grafik-zajec/';
$siteMapper = new siteMapper($startURL);


$crawl_txt = $siteMapper->run();

exit;
file_put_contents("sitemap.html", $crawl_txt);

if( isset($_GET["no_cron"]) ){
	header("Location: index.php"); 
	exit;
}else{
	echo "done";
}
?>