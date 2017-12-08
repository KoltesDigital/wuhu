<?
if (!defined("ADMIN_DIR")) exit();

global $settings;
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
  $data["id"] = $_POST["entryid"];
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
if ($_POST["entryid"]) {
  $msg = "";
  $id = perform($msg);
  if ($id) {
    echo "<div class='alert alert-success' role='alert'>Update successful!</div>";
  } else {
    echo "<div class='failure'>Error: ".$msg."</div>";
  }
}
global $page;
if ($_GET["id"]) {
  $entry = SQLLib::selectRow(sprintf_esc("select * from compoentries where id=%d",$_GET["id"]));
  if ($entry->userid != $_SESSION["logindata"]->id)
    die("nice try.");

  $compo = get_compo($entry->compoid);

  $filedir = get_compoentry_dir_path( $entry );
  if (!$filedir)
    die("Unable to find compo entry dir!");

  if ($_GET["select"]) {
    $lock = new OpLock();
    $fn = basename($_GET["select"]);
    if (file_exists($filedir . $fn)) {
      $upload = array(
        "filename" => $fn,
      );
      SQLLib::UpdateRow("compoentries",$upload,"id=".(int)$_GET["id"]);
      header( "Location: ".build_url($page,array("id"=>(int)$_GET["id"])) );
      exit();
    }
  }

  if ($_GET["delete"]) {
    $lock = new OpLock();
    $fn = basename($_GET["delete"]);
    if (file_exists($filedir . $fn)) {
      unlink($filedir . $fn);
      header( "Location: ".build_url($page,array("id"=>(int)$_GET["id"])) );
      exit();
    }
  }

?>
<form action="<?=build_url($page,array("id"=>(int)$_GET["id"])) ?>" method="post" enctype="multipart/form-data">
<div class="form-group row">
  <label for="title" class="col-sm-3 control-label">Title</label>
  <div class="col-sm-9">
    <input id="title" class="form-control" name="title" type="text" value="<?=_html($entry->title)?>" required='yes'/>
  </div>
</div>
<div class="form-group row">
  <label for="author" class="col-sm-3 control-label">Author</label>
  <div class="col-sm-9">
    <input id="author" class="form-control" name="author" type="text" value="<?=_html($entry->author)?>"/>
  </div>
</div>
<div class="form-group row">
  <label for="comment" class="col-sm-3 control-label">Comment<br/><small>(this will be shown on the compo slide)</small></label>
  <div class="col-sm-9">
    <textarea id="comment" class="form-control" name="comment" rows="5"><?=_html($entry->comment)?></textarea>
  </div>
</div>
<div class="form-group row">
  <label for='orgacomment' class="col-sm-3 control-label">Comment for the organizers<br/><small>(this will NOT be shown anywhere)</small></label>
  <div class="col-sm-9">
    <textarea id="orgacomment" class="form-control" name="orgacomment" rows="5"><?=_html($entry->orgacomment)?></textarea>
  </div>
</div>
<div class="form-group row">
  <label for='screenshot' class="col-sm-3 control-label">Screenshot</label>
  <div class="col-sm-9">
    <img id='screenshot' src='screenshot.php?id=<?=(int)$_GET["id"]?>&amp;show=thumb' alt='thumb'/>
    <input id='screenshot' name="screenshot" type="file" accept="image/*" />
    <p class="help-block">(optional - JPG, GIF or PNG!)</p>
  </div>
</div>
<div class="form-group row">
  <label class="col-sm-3 control-label">Uploaded files</label>
  <div class="col-sm-9">
<table id='uploadedfiles' class="table">
<thead>
<tr>
  <th>Name</th>
  <th>Action</th>
</tr>
</thead>
<tbody>
<?
  $a = glob($filedir . "*");
  foreach ($a as $v)
  {
    $v = basename($v);
?>
<tr class='<?=($v == $entry->filename?"fileselected":"fileunselected")?>'>
  <td><?=$v?></td>
  <td><?
  if ($v == $entry->filename) {
    echo "<i>Currently selected file</i>";
  } else {
    printf("<a href='%s&amp;select=%s'>Select this file</a>\n",$_SERVER["REQUEST_URI"],rawurlencode($v));
    printf("<a href='%s&amp;delete=%s' class='deletefile'>Delete this file</a>\n",$_SERVER["REQUEST_URI"],rawurlencode($v));
  }
  ?></td>
</tr>
<?
  }
?>
</tbody>
</table>
<?if (count($a)>1) {?>
    <div class="alert alert-danger" role="alert">Hint: having only <u>ONE</u> file decreases the chances of having the wrong version played!</div>
<?}?>
  </div>
</div>
<div class="form-group row">
  <label for='entryfile' class="col-sm-3 control-label">Upload new file</label>
  <div class="col-sm-9">
    <input id='entryfile' name="entryfile" type="file"/>
    <p class="help-block">(max. <?=ini_get("upload_max_filesize")?> - if you want to upload a bigger file, just upload a dummy text file here and ask the organizers!)</p>
  </div>
</div>
<div class="form-group row">
  <div class="col-sm-9 col-sm-offset-3">
    <input name="entryid" type='hidden' value="<?=(int)$_GET["id"]?>" />
    <button class="btn btn-default btn-block" type="submit">Go!</button>
  </div>
</div>
</form>
<?
} else {
  $entries = SQLLib::selectRows(sprintf_esc("select * from compoentries where userid=%d",get_user_id()));
  echo "<div class='row' id='editmyentries'>\n";
  global $entry;
  foreach ($entries as $entry)
  {
    $compo = get_compo( $entry->compoid );
    echo "<div class='col-sm-6 col-md-4'><div class='thumbnail'>\n";
    printf("<a href='screenshot.php?id=%d' target='_blank'><img src='screenshot.php?id=%d&amp;show=thumb'/></a>\n",$entry->id,$entry->id);
    echo "<div class='caption'>";
    printf("<div class='compo'>%s</div>\n",_html($compo->name));
    printf("<div class='title'><b>%s</b> - %s</div>\n",_html($entry->title),_html($entry->author));

    if ($compo->uploadopen || $compo->updateopen)
      printf("<div class='editlink'><a href='%s&amp;id=%d'>Edit entry</a></div>",$_SERVER["REQUEST_URI"],$entry->id );

    run_hook("editentries_endrow",array("entry"=>$entry));

    echo "</div></div></div>\n";
  }
  echo "</div>";
}
?>
