<?
if (!defined("ADMIN_DIR")) exit();

run_hook("register_start");

function validate() {
  if (strlen($_POST["username"])<3)
  {
    echo "<div class='alert alert-danger' role='alert'>This username is too short, must be at least 4 characters!</div>";
    return 0;
  }
  if (strlen($_POST["password"])<4)
  {
    echo "<div class='alert alert-danger' role='alert'>This password is too short, must be at least 4 characters!</div>";
    return 0;
  }
  if (!preg_match("/^[a-zA-Z0-9]{3,}$/",$_POST["username"]))
  {
    echo "<div class='alert alert-danger' role='alert'>This username contains invalid characters!</div>";
    return 0;
  }
  /*
  if (!preg_match("/^[a-zA-Z0-9]{4,}$/",$_POST["password"]))
  {
    echo "<div class='alert alert-danger' role='alert'>This password contains invalid characters!</div>";
    return 0;
  }
  */
    if (strcmp($_POST["password"],$_POST["password2"])!=0)
    {
    echo "<div class='alert alert-danger' role='alert'>Passwords don't match!</div>";
    return 0;
  }

  $r = SQLLib::selectRows(sprintf_esc("select * from users where `username`='%s'",$_POST["username"]));
  if ($r)
  {
    echo "<div class='alert alert-danger' role='alert'>This username is already taken!</div>";
    return 0;
  }

  $r = SQLLib::selectRow(sprintf_esc("select * from votekeys where `votekey`='%s'",$_POST["votekey"]));
  if (!$r)
  {
    echo "<div class='alert alert-danger' role='alert'>This votekey is invalid!</div>";
    return 0;
  }
  if ($r->userid)
  {
    echo "<div class='alert alert-danger' role='alert'>This votekey is already in use!</div>";
    return 0;
  }

  return 1;
}
$success = false;
if ($_POST["username"]) {
  if (validate())
  {
    $userdata = array(
      "username"=> ($_POST["username"]),
      "password"=> hashPassword($_POST["password"]),
      "nickname"=> ($_POST["nickname"] ? $_POST["nickname"] : $_POST["username"]),
      "group"=> ($_POST["group"]),
      "regip"=> ($_SERVER["REMOTE_ADDR"]),
      "regtime"=> (date("Y-m-d H:i:s")),
    );
    $error = "";
    run_hook("register_processdata",array("data"=>&$userdata));
    if (!$error)
    {
      $trans = new SQLTrans();
      $userID = SQLLib::InsertRow("users",$userdata);
      SQLLib::UpdateRow("votekeys",array("userid"=>$userID),sprintf_esc("`votekey`='%s'",$_POST["votekey"]));
      echo "<div class='alert alert-success' role='alert'>Registration successful!</div>";
      $success = true;
    }
    else
    {
      echo "<div class='failure'>"._html($error)."</div>";
    }
  }
}
if(!$success)
{
?>
<form action="<?=build_url("Login")?>" method="post" id='registerForm'>
<div class="form-group row">
  <label for="username" class="col-sm-3 control-label">Username</label>
  <div class="col-sm-9">
    <input id="username" class="form-control" name="username" type="text" value="<?=_html($_POST["username"])?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for="password" class="col-sm-3 control-label">Password</label>
  <div class="col-sm-9">
    <input id="password" class="form-control" name="password" type="password" required='yes' />
  </div>
</div>
<div class="form-group row">
  <label for="password2" class="col-sm-3 control-label">Password again</label>
  <div class="col-sm-9">
    <input id="password2" class="form-control" name="password2" type="password" required='yes' />
  </div>
</div>
<div class="form-group row">
  <label for="votekey" class="col-sm-3 control-label">Votekey: <small>(Get one at the infodesk to be able to register!)</small></label>
  <div class="col-sm-9">
    <input id="votekey" class="form-control" name="votekey" type="text" value="<?=_html($_POST["votekey"])?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for="nickname" class="col-sm-3 control-label">Nick/Handle</label>
  <div class="col-sm-9">
    <input id="nickname" class="form-control" name="nickname" type="text" value="<?=_html($_POST["nickname"])?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for="group" class="col-sm-3 control-label">Group: (if any)</label>
  <div class="col-sm-9">
    <input id="group" class="form-control" name="group" type="text" value="<?=_html($_POST["group"])?>"/>
  </div>
</div>
<?
run_hook("register_endform");
?>
<div id='regsubmit' class="form-group row">
  <div class="col-sm-9 col-sm-offset-3">
    <button class="btn btn-default btn-block" type="submit">Go!</button>
  </div>
</div>
</form>
<?
}

run_hook("register_end");
?>
