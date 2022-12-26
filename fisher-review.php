<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//Declare Title, Content, Author
$pgAuthor = "David Sangrey";
$pgContent = "View the details of a Seal Case";
$useIP = 1; //1 if Yes, 0 if No.

//UserSpice Required
require_once '../users/init.php';  //make sure this path is correct!
require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
if (!securePage($_SERVER['PHP_SELF'])) {
  die();
}

if (!isset($_GET['cne'])) {
  usError("No Case Specified.");
  header('Location: cases-list.php');
  die();
}

//Who are we working with?
$beingManaged = $_GET['cne'];
$beingManaged = intval($beingManaged);

//DB Info
$db = include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($db['server'], $db['user'], $db['pass'], 'records', $db['port']);

//All Case Info
$stmtCaseInfo = $mysqli->prepare("WITH sealsCTI
AS
(
SELECT MIN(ID), seal_ID, seal_name
FROM sealsudb.staff
GROUP BY seal_ID
)
SELECT client_nm, current_sys, current_planet, site_coords, platform_name, status_name, color_name, notes, case_created, rev_date, rev_stat_text, COALESCE(seal_name, CONCAT('SEAL ID', reviewer), 'Not Assigned') as reviewer, rev_notes
FROM cases AS c
JOIN case_kf AS cs ON cs.case_ID = c.case_ID
JOIN case_history AS ch ON ch.ch_ID = c.last_ch_id
JOIN review_info as ri on ri.caseID = c.case_ID
JOIN lookups.status_lu AS slu ON slu.status_id = ch.case_stat
JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
JOIN lookups.case_color_lu AS ccl ON ccl.color_id = ch.code_color
JOIN lookups.review_stat_lu as rsl on rsl.rev_stat_ID = ri.review_status
LEFT JOIN sealsCTI as ss on ss.seal_ID = ri.reviewer
WHERE c.case_ID = ?");
$stmtCaseInfo->bind_param("i", $beingManaged);
$stmtCaseInfo->execute();
$resultCaseInfo = $stmtCaseInfo->get_result();
$stmtCaseInfo->close();
if ($resultCaseInfo->num_rows === 0) {
  usError("No Case Details Found");
  header('Location: cases-list.php');
  die();
}

//All Assigned Seals
$stmtAssigned = $mysqli->prepare("WITH sealsCTI
AS
(
SELECT MIN(ID), seal_ID, seal_name
FROM sealsudb.staff
GROUP BY seal_ID
)
SELECT COALESCE(seal_name, CONCAT('SEAL ID ', seal_kf_id), 'MISSING INFORMATION') AS seal_name, dispatch, support, self_dispatch
FROM case_assigned AS ca
LEFT JOIN sealsCTI AS ss ON ss.seal_ID = ca.seal_kf_id
WHERE case_ID = ?;");
$stmtAssigned->bind_param("i", $beingManaged);
$stmtAssigned->execute();
$resultAssigned = $stmtAssigned->get_result();
$stmtAssigned->close();

if (($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['formtype'] == "delCase") && (hasPerm([7, 8, 9, 10, 19], $user->data()->id))) {
  foreach ($_REQUEST as $key => $value) {
    $lore[$key] = strip_tags(stripslashes(str_replace(["'", '"'], '', $value)));
  }
  $stmt = $mysqli->prepare('CALL spDeleteCase(?,?,?,?)');
  $stmt->bind_param('iiss', $beingManaged, $user->data()->id, $lore['notes'], $lgd_ip);
  $stmt->execute();
  $stmt->close();
  usSuccess("Case Marked for Deletion");
  header("Location: cases-list.php");
  die();
}
?>
<h2>Welcome, <?= echousername($user->data()->id); ?>.</h2>
<p>You are Reviewing Paperwork for Case # <?= $beingManaged; ?> <a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
<br>
<h3>Case Info</h3>
<br>
<h5>Basic Information</h5>
<table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
  <thead>
    <tr>
      <th>Client Name</th>
      <th>System</th>
      <th>Platform</th>
      <th>Paperwork Filed</th>
    </tr>
  </thead>
  <tbody>
    <?php
    while ($rowCaseInfo = $resultCaseInfo->fetch_assoc()) { ?>
      <tr>
        <td><?= $rowCaseInfo["client_nm"] ?></td>
        <td><?= $rowCaseInfo["current_sys"] ?></td>
        <td><?= $rowCaseInfo["platform_name"] ?></td>
        <td><?= $rowCaseInfo["case_created"] ?></td>
      </tr>
  </tbody>
</table>
<br>
<h5>Situation Information</h5>
<table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
  <thead>
    <tr>
      <th>Planet</th>
      <th>Coordinates</th>
      <th>Case Color</th>
      <th>Case Status</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?= $rowCaseInfo["current_planet"] ?></td>
      <td><?= $rowCaseInfo["site_coords"] ?></td>
      <td><?= $rowCaseInfo["color_name"] ?></td>
      <td><?= $rowCaseInfo["status_name"] ?></td>
    </tr>
  </tbody>
</table>
<br>
<h5>Case Notes</h5>
<table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
  <thead>
    <tr>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?= $rowCaseInfo["notes"] ?></td>
    </tr>
  </tbody>
</table>
<br>
<h5>Review Information</h5>
<table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
  <thead>
    <tr>
      <th>Reviewer</th>
      <th>Status</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?= $rowCaseInfo["reviewer"] ?></td>
      <td><?= $rowCaseInfo["rev_stat_text"] ?></td>
      <td><?= $rowCaseInfo["rev_date"] ?></td>
    </tr>
  </tbody>
</table>
<?php if (hasPerm([7, 8, 9, 10, 19], $user->data()->id)) { ?>
  <br>
  <h5>Reviewer Notes</h5>
  <table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
    <thead>
      <tr>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= $rowCaseInfo["rev_notes"] ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
<?php
    }
    $resultCaseInfo->free();
?>
<br>
<h3>Responder Information</h3>
<table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
  <thead>
    <tr>
      <th>Responder</th>
      <th>Responder Type</th>
      <th>Self-Dispatched?</th>
    </tr>
  </thead>
  <tbody>
    <?php
    while ($rowAssigned = $resultAssigned->fetch_assoc()) {
      $field1name = $rowAssigned["seal_name"];
      $field2name = $rowAssigned["dispatch"];
      $field3name = $rowAssigned["support"];
      $field4name = $rowAssigned["self_dispatch"]; ?>
      <tr>
        <td><?= $field1name ?></td>
        <?php if ($rowAssigned["dispatch"] == 0 && $rowAssigned["support"] == 0) { ?>
          <td>Primary Seal</td>
        <?php } elseif ($rowAssigned["dispatch"] == 1 && $rowAssigned["support"] == 0) { ?>
          <td>Dispatcher</td>
        <?php } elseif ($rowAssigned["dispatch"] == 0 && $rowAssigned["support"] == 1) { ?>
          <td>Supporting Seal</td>
        <?php } elseif ($rowAssigned["dispatch"] == 1 && $rowAssigned["support"] == 1) { ?>
          <td>Supporting Dispatcher</td>
        <?php }
        echo $rowAssigned["self_dispatch"] == 0 ? "<td>No</td>" : "<td>Yes</td>"; ?>
      </tr>
    <?php
    }
    $resultAssigned->free();
    ?>
  </tbody>
</table>
<?php if (hasPerm([7, 8, 9, 10, 19], $user->data()->id)) { ?>
  <h4>Review Access:</h4>
  <a href="fisher-edit.php?cne=<?= $beingManaged ?>" class="btn btn-small btn-warning">Edit This Case</a> <button class="btn btn-danger btn-small" data-target="#moDel" data-toggle="modal" type="button">Mark Case for Deletion</button>
  <div class="modal fade" id="moDel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel" style="color:black;">Mark Case for Deletion</h5><button class="close" data-dismiss="modal" type="button"><span>&times;</span></button>
        </div>
        <div class="modal-body" style="color:black;">
          <form action="?del&cne=<?= $beingManaged; ?>" method="post">
            <input hidden type="text" name="formtype" value="delCase">
            <div class="input-group mb-3">
              <textarea class="form-control" name="notes" pattern="[\x20-\x7A]+" minlength="10" placeholder="Reason for Deletion (Required)" required rows="5" style="color:black;"><?= $data['notes'] ?? '' ?></textarea>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary" type="submit">Submit</button><button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php } else { ?>
  <h4>To modify incorrect information, please <a class="btn btn-small btn-primary" href="mailto:cyberseals@hullseals.space?subject=Case%20Edit%20Request&body=Edit%20requested%20to%20case%20<?= $beingManaged ?>!" target="_blank">contact the CyberSeals.</a></h4>
<?php } ?>
<br>
<p><a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
<br>
<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>