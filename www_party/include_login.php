<?
if (!defined("ADMIN_DIR")) exit();

if (is_user_logged_in())
{
  header( "Location: ".build_url("News",array("login"=>"alreadyloggedin")) );
  exit();
}

run_hook("login_start");

if ($_POST["login"])
{
  $_SESSION["logindata"] = NULL;

  $userID = SQLLib::selectRow(sprintf_esc("select id from users where `username`='%s' and `password`='%s'",$_POST["login"],hashPassword($_POST["password"])))->id;

  run_hook("login_authenticate",array("userID"=>&$userID));

  if ($userID)
  {
    $_SESSION["logindata"] = SQLLib::selectRow(sprintf_esc("select * from users where id=%d",$userID));
    header( "Location: ".build_url("News",array("login"=>"success")) );
  }
  else
  {
    header( "Location: ".build_url("Login",array("login"=>"failure")) );
  }
  exit();
}
if ($_GET["login"]=="failure")
  echo "<div class='alert alert-danger' role='alert'>Login failed!</div>";
?>
<form action="<?=build_url("Login")?>" method="post" id='loginForm'>
<div class="form-group row">
  <label for="loginusername" class="col-sm-3 control-label">Username</label>
  <div class="col-sm-9">
    <input id="loginusername" class="form-control" name="login" type="text" required='yes' />
  </div>
</div>
<div class="form-group row">
  <label for="loginpassword" class="col-sm-3 control-label">Password</label>
  <div class="col-sm-9">
    <input id="loginpassword" class="form-control" name="password" type="password" required='yes' />
  </div>
</div>
<div class="form-group row">
  <div class="col-sm-9 col-sm-offset-3">
    <button class="btn btn-default btn-block" type="submit">Go!</button>
  </div>
</div>
</form>
<?
run_hook("login_end");
?>
