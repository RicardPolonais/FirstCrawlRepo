<?
//phpinfo();exit;
include "login_database.php";



if(isset($_POST["submit_login"])){
  if( $DB["users"][$_POST["username"]] == md5($_POST["password"]) ){
	  $_SESSION["logged"] = $_POST["username"];
  }else{
	  unset($_SESSION["logged"]);
	  $errorMsg = "Invalid username or password.";
  }
  
}
if($_SESSION["logged"]) {
   header("Location: admin_panel.php"); 
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <title>Login</title>
</head>
<body>
  <form name="input" action="" method="post">
    <label for="username">Username:</label><input type="text" value="" id="username" name="username" />
    <label for="password">Password:</label><input type="password" value="" id="password" name="password" />
    <div class="error"><?= $errorMsg ?></div>
    <input type="submit" value="Login" name="submit_login" />
  </form>

<?
// On the front-end, allow a visitor to view the sitemap.html page.
?>
  <p>
    <a href="sitemap.html">View sitemap</a>
  </p>
</body>
</html>

