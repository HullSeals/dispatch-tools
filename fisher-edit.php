<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//Declare Title, Content, Author
$pgAuthor = "David Sangrey";
$pgContent = "Edit the details of a Seal Case";
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
$platformList = [];
$res = $mysqli->query('SELECT * FROM lookups.platform_lu ORDER BY platform_id');
while ($platform = $res->fetch_assoc()) {
  $platformList[$platform['platform_id']] = $platform['platform_name'];
}
$statusList = [];
$resStatus = $mysqli->query('SELECT * FROM lookups.status_lu ORDER BY status_id');
while ($casestat = $resStatus->fetch_assoc()) {
  if ($casestat['status_name'] == 'Open' || $casestat['status_name'] == 'On Hold' || $casestat['status_name'] == 'Delete Case') {
    continue;
  }
  $statusList[$casestat['status_id']] = $casestat['status_name'];
}
$colorList = [];
$resColor = $mysqli->query('SELECT * FROM lookups.case_color_lu where color_id > 7');
while ($color = $resColor->fetch_assoc()) {
  $colorList[$color['color_id']] = $color['color_name'];
}

//All Case Info
$stmtCaseInfo = $mysqli->prepare("SELECT client_nm, current_sys, current_planet, site_coords, platform_name,
status_name, color_name, notes, case_created, rev_notes, note_worth, review_status, db_update
FROM cases AS c
JOIN case_kf AS cs ON cs.case_ID = c.case_ID
JOIN case_history AS ch ON ch.ch_ID = c.last_ch_id
JOIN review_info as ri on ri.caseID = c.case_ID
JOIN lookups.status_lu AS slu ON slu.status_id = ch.case_stat
JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
JOIN lookups.case_color_lu AS ccl ON ccl.color_id = ch.code_color
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

$validationErrors = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['formtype'] == "updateCase") {
  foreach ($_REQUEST as $key => $value) {
    $lore[$key] = strip_tags(stripslashes(str_replace(["'", '"'], '', $value)));
  }
  if (!isset($lore['review_status'])) {
    usError("Error! No review status set! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['platform'])) {
    usError("Error! No platform set! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['client_nm']) || strlen($data['client_nm']) > 45) {
    usError("Error in client name! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['curr_sys']) || strlen($data['curr_sys']) > 100) {
    usError("Error in system name! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['color'])) {
    usError("Error! No case color set! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['notes'])) {
    usError("Error! No notes set! Please try again.");
    $validationErrors += 1;
  }
  if (!isset($lore['site_coords'])) {
    usError("Error! No coordinates set! Please try again.");
    $validationErrors += 1;
  }
  if ($validationErrors == 0) {
    $stmt = $mysqli->prepare('CALL spUpdateKFCase(?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iiissssiiss', $beingManaged, $lore['status'], $lore['platform'], $lore['client_nm'], $lore['curr_sys'], $lore['current_planet'], $lore['site_coords'], $lore['color'], $user->data()->id, $lore['notes'], $lgd_ip);
    $stmt->execute();
    $stmt->close();
    $stmt2 = $mysqli->prepare('CALL spCaseReviewUpdate(?,?,?,?,?,?,?)');
    $stmt2->bind_param('iiisiis', $beingManaged, $lore['review_status'], $user->data()->id, $lore['revnotes'], $lore['noteworthy'], $lore['dbupdate'], $lgd_ip);
    $stmt2->execute();
    $stmt2->close();
    usSuccess("Case Edits Saved Successfully");
    header("Location: ?cne=$beingManaged");
    die();
  }
}
?>
<form action="?updateinfo&cne=<?= $beingManaged; ?>" method="post">
  <input hidden type="text" name="formtype" value="updateCase">
  <h2>Welcome, <?= echousername($user->data()->id); ?>.</h2>
  <p>You are Reviewing Paperwork for Case # <?= $beingManaged; ?> <?php if (hasPerm([7, 8, 9, 10, 19], $user->data()->id)) { ?>
      <br><br><strong>Review Access:</strong>
      <a href="review-list.php" class="btn btn-small btn-warning">Review Case Dashboard</a><?php } ?> <a href="fisher-review.php?cne=<?= $beingManaged; ?>" class="btn btn-small btn-danger" style="float: right;">Go Back</a>
  </p>
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
          <td><input class="form-control" name="client_nm" pattern="[\x20-\x7A]+" minlength="3" placeholder="Client Name" required type="text" value="<?= $rowCaseInfo["client_nm"] ?>"></td>
          <td><input class="form-control" name="curr_sys" pattern="[\x20-\x7A]+" minlength="3" placeholder="System" required type="text" value="<?= $rowCaseInfo["current_sys"] ?>"></td>
          <td>
            <select class="custom-select" id="inputGroupSelect03" name="platform" required>
              <?php foreach ($platformList as $platformId => $platformName) {
                $platformName == $rowCaseInfo["platform_name"] ? $selected = "selected" : $selected = "";
                echo '<option value="' . $platformId . '"' . $selected . '>' . $platformName . '</option>';
              } ?>
            </select>
          </td>
          <td><?= $rowCaseInfo["case_created"] ?></td>
        </tr>
    </tbody>
  </table> <br>
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
        <td><input class="form-control" name="current_planet" pattern="[\x20-\x7A]+" minlength="1" maxlength="10" placeholder="Current Planet" required type="text" value="<?= $rowCaseInfo["current_planet"] ?>"></td>
        <td><input class="form-control" name="site_coords" pattern="(\+?|-)\d{1,3}\.\d{3}\,(\+?|-)\d{1,3}\.\d{3}" title="+/-000.000, +/-000.000" placeholder="Site Coords" required type="text" value="<?= $rowCaseInfo["site_coords"] ?>"></td>
        <td>
          <select class="custom-select" id="inputGroupSelect01" name="color" required>
            <?php foreach ($colorList as $colorId => $colorName) {
              $colorName == $rowCaseInfo["color_name"] ? $selected = "selected" : $selected = "";
              echo '<option value="' . $colorId . '"' . $selected . '>' . $colorName . '</option>';
            } ?>
          </select>
        </td>
        <td>
          <select class="custom-select" id="inputGroupSelect02" name="status" required>
            <?php foreach ($statusList as $statusId => $statusName) {
              $statusName == $rowCaseInfo["status_name"] ? $selected = "selected" : $selected = "";
              echo '<option value="' . $statusId . '"' . $selected . '>' . $statusName . '</option>';
            } ?>
          </select>
        </td>
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
        <td><textarea minlength="10" pattern="[\x20-\x7F]+" class="form-control" name="notes" rows="5"><?= $rowCaseInfo["notes"] ?></textarea>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <h5>Review Status</h5>
  <table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
    <thead>
      <tr>
        <th>Review Status</th>
        <th>"Noteworthy" Case</th>
        <th>DB Update</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <select class="custom-select" id="inputGroupSelect04" name="review_status" required>
            <option value="1" <?= $rowCaseInfo["review_status"] == 1 ? "selected" : ""; ?>>Needs Review</option>
            <option value="2" <?= $rowCaseInfo["review_status"] == 2 ? "selected" : ""; ?>>In Review</option>
            <option value="3" <?= $rowCaseInfo["review_status"] == 3 ? "selected" : ""; ?>>Review Complete</option>
          </select>
        </td>
        <td>
          <select class="custom-select" id="inputGroupSelect04" name="noteworthy" required>
            <option value="0" <?= $rowCaseInfo["note_worth"] == 0 ? "selected" : ""; ?>>Not Noteworthy</option>
            <option value="1" <?= $rowCaseInfo["note_worth"] == 1 ? "selected" : ""; ?>>Noteworthy</option>
          </select>
        </td>
        <td>
          <select class="custom-select" id="inputGroupSelect04" name="dbupdate" required>
            <option value="0" <?= $rowCaseInfo["db_update"] == 0 ? "selected" : ""; ?>>No DB Update</option>
            <option value="1" <?= $rowCaseInfo["db_update"] == 1 ? "selected" : ""; ?>>Needs Updated</option>
          </select>
        </td>
    </tbody>
  </table>
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
        <td><textarea minlength="10" pattern="[\x20-\x7F]+" class="form-control" name="revnotes" rows="5"><?= $rowCaseInfo["rev_notes"] ?></textarea>
        </td>
      </tr>
    <?php }
      $resultCaseInfo->free();
    ?>
    </tbody>
  </table>
  <button type="submit" class="btn btn-warning">Update Case and Review Info</button>
</form>
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
</table>
<p><?php if (hasPerm([7, 8, 9, 10, 19], $user->data()->id)) { ?>
    <strong>Review Access:</strong>
    <a href="review-list.php" class="btn btn-small btn-warning">Review Case Dashboard</a><?php } ?><a href="fisher-review.php?cne=<?= $beingManaged; ?>" class="btn btn-small btn-danger" style="float: right;">Go Back</a>
</p>
<hr>
<br>
<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>
