<?
session_start();

function PA( $tablica ){
	echo "<pre>";
	print_r( $tablica );
	echo "</pre>";
	//exit;
}

$DB=array(
	"users" => array(
		"admin" => md5("password")
	)
);


?>