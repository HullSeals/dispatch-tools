<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//UserSpice Required
require_once '../../users/init.php';  //make sure this path is correct!
if (!securePage($_SERVER['PHP_SELF'])){die();}

?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta content="Delayed Case Status" name="description">
<meta http-equiv="refresh" content="300">
<title>Delayed Case Status | The Hull Seals</title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<?php include '../../assets/includes/headerCenter.php'; ?>
</head>
<body>
    <div id="home">
      <?php include '../../assets/includes/menuCode.php';?>
        <section class="introduction container">
	    <article id="intro3">
    <h1>Delayed Case Status Board</h1>
    <p>Sometimes, a case can't be completed quickly. Here you can view any pending cases the Seals have, and the status of those cases.</p>
    <?php
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = include 'db.php';
    $mysqli = new mysqli($db['server'], $db['user'], $db['pass'], $db['db'], $db['port']);
    $stmt = $mysqli->prepare("SELECT ID, delayed_name, case_text, last_updated, updated_by FROM casestatus JOIN lookups.delayed_lu ON delayed_id = case_status WHERE case_status != 3");
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<h3>Returning all Pending Cases: ";
    echo nl2br ("</h3>");
    if($result->num_rows === 0) {
      echo "<h2>No Pending Cases. Good Work, Seals!</h2>";
    }
    else {
    echo '<table class="table table-dark table-striped table-bordered table-hover table-responsive-md">
          <tr>
              <td>Case ID</td>
              <td>Case Status</td>
              <td>Notes</td>
              <td>Last Updated</td>
              <td>From</td>
          </tr>';

        while ($row = $result->fetch_assoc()) {
            $field1name = $row["ID"];
            $field2name = $row["delayed_name"];
            $field3name = $row["case_text"];
            $field4name = $row["last_updated"];
            $field5name = $row["updated_by"];
            echo '<tr>
                      <td>'.$field1name.'</td>
                      <td>'.$field2name.'</td>
                      <td>';
                      if (strlen($field3name) > 200) {
                        echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#MO'.$field1name.'">Notes Too Large. Overflowed...</button>';
                        echo '<div class="modal fade" id="MO'.$field1name.'" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:black;" id="exampleModalLabel">Delayed Case Notes</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="color:black;">
        Overflowed Case Notes for case '.$field1name.': <br><br>'.$field3name.'
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>';
                      }
                      else {
                        echo $field3name;
                        echo '</td>';
                      }
                      echo '
                      <td>'.$field4name.'</td>
                      <td>'.$field5name.'</td>
                  </tr>
        ';}
        echo '</table>';
        $result->free();
      }
    ?>
    <p><a href=".." class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
    <br />
  </article>
  <div class="clearfix"></div>
</section>
</div>
<?php include '../../assets/includes/footer.php'; ?>
</body>
</html>
