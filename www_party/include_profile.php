<?
if (!defined("ADMIN_DIR")) exit();

if ($_POST["nickname"]) {
  global $userdata;
  $userdata = array(
    "group"=> ($_POST["group"]),
  );
  if ($_POST["nickname"])
    $userdata["nickname"] = $_POST["nickname"];
  run_hook("profile_processdata",array("data"=>&$userdata));
  if ($_POST["password"]) {
    if ($_POST["password"]!=$_POST["password2"]) {
      echo "<div class='alert alert-danger' role='alert'>Passwords don't match!</div>";
    } else {
      $userdata["password"] = hashPassword($_POST["password"]);
    }
  }
  SQLLib::UpdateRow("users",$userdata,sprintf_esc("id='%d'",get_user_id()));
  echo "<div class='alert alert-success' role='alert'>Profile editing successful!</div>";
}
global $user;
$user = SQLLib::selectRow(sprintf_esc("select * from users where id='%d'",get_user_id()));
global $page;
?>
<form action="<?=build_url("ProfileEdit")?>" method="post" id='profileForm'>
<div class="form-group row">
  <label class="col-sm-3 control-label">Username</label>
  <div class="col-sm-9">
    <b><?=_html($user->username)?></b>
  </div>
</div>
<div class="form-group row">
  <label for="password" class="col-sm-3 control-label">New password:<br/><small>(only if you want to change it)</small></label>
  <div class="col-sm-9">
    <input name="password" class="form-control" type="password" id="password" />
  </div>
</div>
<div class="form-group row">
  <label for="password2" class="col-sm-3 control-label">New password again</label>
  <div class="col-sm-9">
    <input name="password2" class="form-control" type="password" id="password2" />
  </div>
</div>
<div class="form-group row">
  <label for="nickname" class="col-sm-3 control-label">Nick/Handle</label>
  <div class="col-sm-9">
    <input name="nickname" class="form-control" type="text" id="nickname" value="<?=_html($user->nickname)?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for="group" class="col-sm-3 control-label">Group: (if any)</label>
  <div class="col-sm-9">
    <input name="group" class="form-control" type="text" id="group" value="<?=_html($user->group)?>"/>
  </div>
</div>
<?
run_hook("profile_endform");
?>
<div id='regsubmit' class="form-group row">
  <div class="col-sm-9 col-sm-offset-3">
    <button class="btn btn-default btn-block" type="submit">Go!</button>
  </div>
</div>
</form>
