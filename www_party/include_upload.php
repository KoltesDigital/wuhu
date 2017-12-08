<?
if (!defined("ADMIN_DIR")) exit();

global $settings;
include_once(ADMIN_DIR . "/thumbnail.inc.php");

function perform(&$msg)
{
  global $settings;
  if (!is_user_logged_in()) {
    $msg = "You got logged out :(";
    return 0;
  }
  $data = array();
  $meta = array("title","author","comment","orgacomment");
  foreach($meta as $m) $data[$m] = $_POST[$m];
  $data["compoID"] = $_POST["compo"];
  $data["userID"] = get_user_id();
  $data["localScreenshotFile"] = $_FILES['screenshot']['tmp_name'];
  $data["localFileName"] = $_FILES['entryfile']['tmp_name'];
  $data["originalFileName"] = $_FILES['entryfile']['name'];
  if (handleUploadedRelease($data,$out))
  {
    return $out["entryID"];
  }

  $msg = $out["error"];
  return 0;
}
if ($_POST) {
  $msg = "";
  $id = perform($msg);
  if ($id) {
    echo "<div class='alert alert-success' role='alert'>Upload successful! Your entry number is <b>".$id."</b>.</div>";
  } else {
    echo "<div class='failure'>".$msg."</div>";
  }
}

$s = SQLLib::selectRows("select * from compos where uploadopen>0 order by start");
if ($s) {
global $page;
?>
<form action="<?=$_SERVER["REQUEST_URI"]?>" method="post" enctype="multipart/form-data" id='uploadEntryForm'>
<div class="form-group row">
  <label for='compo' class="col-sm-3 control-label">Compo</label>
  <div class="col-sm-9">
    <select id='compo' class="form-control" name="compo" required='yes'>
      <option value=''>-- Please select a compo:</option>
<?
foreach($s as $t)
  printf("      <option value='%d'%s>%s</option>\n",$t->id,$t->id==$_POST["compo"] ? ' selected="selected"' : "",$t->name);
?>
    </select>
  </div>
</div>
<div class="form-group row">
  <label for='title' class="col-sm-3 control-label">Title</label>
  <div class="col-sm-9">
    <input id='title' class="form-control" name="title" type="text" value="<?=_html($_POST["title"])?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for='author' class="col-sm-3 control-label">Author</label>
  <div class="col-sm-9">
    <input id='author' class="form-control" name="author" type="text" value="<?=_html($_POST["author"])?>"/>
  </div>
</div>
<div class="form-group row">
  <label for="comment" class="col-sm-3 control-label">Comment<br/><small>(this will be shown on the compo slide)</small></label>
  <div class="col-sm-9">
    <textarea id="comment" class="form-control" name="comment" rows="5"><?=_html($_POST["comment"])?></textarea>
  </div>
</div>
<div class="form-group row">
  <label for='orgacomment' class="col-sm-3 control-label">Comment for the organizers<br/><small>(this will NOT be shown anywhere)</small></label>
  <div class="col-sm-9">
    <textarea id="orgacomment" class="form-control" name="orgacomment" rows="5"><?=_html($_POST["orgacomment"])?></textarea>
  </div>
</div>
<div class="form-group row">
  <label for='entryfile' class="col-sm-3 control-label">Uploaded file</label>
  <div class="col-sm-9">
    <input id='entryfile' name="entryfile" type="file" required='yes' />
    <p class="help-block">(max. <?=ini_get("upload_max_filesize")?> - if you want to upload a bigger file, just upload a dummy text file here and ask the organizers!)</p>
  </div>
</div>
<div class="form-group row">
  <label for='screenshot' class="col-sm-3 control-label">Screenshot</label>
  <div class="col-sm-9">
    <input id='screenshot' name="screenshot" type="file" accept="image/*" />
    <p class="help-block">(optional - JPG, GIF or PNG!)</p>
  </div>
</div>
<div class="form-group row">
  <div class="col-sm-9 col-sm-offset-3">
    <button class="btn btn-default btn-block" type="submit">Go!</button>
  </div>
</div>
</form>
<?
} else echo "Sorry, all deadlines are closed!";
?>
