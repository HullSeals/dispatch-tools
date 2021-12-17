<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//UserSpice Required
require_once '../users/init.php';  //make sure this path is correct!
if (!securePage($_SERVER['PHP_SELF'])){die();}
if (!isset($_GET['cne'])) {
  Redirect::to('cases-list.php');
}

//Who are we working with?
$beingManaged = $_GET['cne'];
$beingManaged = intval($beingManaged);

//IP Tracking Stuff
require '../assets/includes/ipinfo.php';

//DB Info
$db = include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($db['server'], $db['user'], $db['pass'], 'records', $db['port']);

//All Case Info
$stmtCaseInfo = $mysqli->prepare("SELECT client_nm, current_sys, current_planet, site_coords, platform_name,
   status_name, color_name, notes, case_created
FROM cases AS c
    JOIN case_kf AS cs ON cs.case_ID = c.case_ID
    JOIN case_history AS ch ON ch.ch_ID = c.last_ch_id
    JOIN lookups.status_lu AS slu ON slu.status_id = ch.case_stat
    JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
    JOIN lookups.case_color_lu AS ccl ON ccl.color_id = ch.code_color
WHERE c.case_ID = ?");
$stmtCaseInfo->bind_param("i", $beingManaged);
$stmtCaseInfo->execute();
$resultCaseInfo = $stmtCaseInfo->get_result();
$stmtCaseInfo->close();
if($resultCaseInfo->num_rows === 0) {
  Redirect::to('cases-list.php');
}

//$rowCaseInfo = $resultCaseInfo->fetch_assoc();
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
//$rowAssigned = $resultAssigned->fetch_assoc();
if (isset($_GET['updateinfo'])) {
  foreach ($_REQUEST as $key => $value) {
      $lore[$key] = strip_tags(stripslashes(str_replace(["'", '"'], '', $value)));
  }
  $stmt = $mysqli->prepare('CALL spUpdateKFCase(?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->bind_param('iiissssiiss', $beingManaged, $lore['status'], $lore['platform'], $lore['client_nm'], $lore['curr_sys'], $lore['current_planet'], $lore['site_coords'], $lore['color'], $user->data()->id, $lore['notes'], $lgd_ip);
  $stmt->execute();
  $stmt->close();
header("Location: ?cne=$beingManaged");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta content="Welcome to the Hull Seals, Elite Dangerous's Premier Hull Repair Specialists!" name="description">
 <title>Case Review | The Hull Seals</title>
 <meta content="David Sangrey" name="author">
 <?php include '../assets/includes/headerCenter.php'; ?>
</head>
<body>
    <div id="home">
      <?php include '../assets/includes/menuCode.php';?>
      <section class="introduction container">
    <article id="intro3">
      <form action="?updateinfo&cne=<?php echo $beingManaged; ?>" method="post">
      <h2>Welcome, <?php echo echousername($user->data()->id); ?>.</h2>
      <p>You are Reviewing Paperwork for Case # <?php echo $beingManaged;?> <a href="fisher-review.php?cne=<?php echo $beingManaged; ?>" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
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
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while ($rowCaseInfo = $resultCaseInfo->fetch_assoc()) {
                    echo '<tr>
                    <td><input aria-label="Client Name" class="form-control" name="client_nm" placeholder="Client Name" required="" type="text" value="'. $rowCaseInfo["client_nm"].'"></td>
                    <td><input aria-label="System" class="form-control" name="curr_sys" placeholder="System" required="" type="text" value="'.$rowCaseInfo["current_sys"].'"></td>
                    <td>
                    <select class="custom-select" id="inputGroupSelect03" name="platform" required="">
                    <option value="1"';
                    if ($rowCaseInfo["platform_name"] == "PC - Odyssey") { echo "selected"; }
                    echo '>PC - Odyssey</option>
                    <option value="2"';
                    if ($rowCaseInfo["platform_name"] == "Xbox") { echo "selected"; }
                    echo '>Xbox</option>
                    <option value="3"';
                    if ($rowCaseInfo["platform_name"] == "PlayStation") { echo "selected"; }
                    echo '>PlayStation</option>
                    <option value="4"';
                    if ($rowCaseInfo["platform_name"] == "PC - Horizons") { echo "selected"; }
                    echo '>PC - Horizons</option>
                    </select>
                    </td>
                   </tr>';
                  ?>
                </tbody>
              </table>    <br>
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
      <?php
        echo '<tr>
        <td><input class="form-control" name="current_planet" placeholder="Current Planet" required="" type="text" value="'. $rowCaseInfo["current_planet"].'"></td>
        <td><input class="form-control" name="site_coords" placeholder="Site Coords" required="" type="text" value="'. $rowCaseInfo["site_coords"].'"></td>
        <td>
        <select class="custom-select" id="inputGroupSelect01" name="color" required="">
        <option value="8"';
        if ($rowCaseInfo["color_name"] == "Lift") { echo "selected"; }
        echo '>Lift</option>
        <option value="9"';
        if ($rowCaseInfo["color_name"] == "Golf") { echo "selected"; }
        echo '>Golf</option>
        <option value="10"';
        if ($rowCaseInfo["color_name"] == "Puck") { echo "selected"; }
        echo '>Puck</option>
        <option value="11"';
        if ($rowCaseInfo["color_name"] == "Pick") { echo "selected"; }
        echo '>Pick</option>
        </select>
        </td>
        <td>
                <select class="custom-select" id="inputGroupSelect02" name="status" required="">
                <option value="2"';
                if ($rowCaseInfo["status_name"] == "Closed - Successful") { echo "selected"; }
                echo '>Closed - Successful</option>
                <option value="3"';
                if ($rowCaseInfo["status_name"] == "Closed - Failed") { echo "selected"; }
                echo '>Closed - Failed</option>
                <option value="4"';
                if ($rowCaseInfo["status_name"] == "Closed - Redirected") { echo "selected"; }
                echo '>Closed - Redirected</option>
                <option value="5"';
                if ($rowCaseInfo["status_name"] == "Closed - Other") { echo "selected"; }
                echo '>Closed - Other</option>
                <option value="6"';
                if ($rowCaseInfo["status_name"] == "Closed - False Case") { echo "selected"; }
                echo '>Closed - False Case</option>
                <option value="8"';
                if ($rowCaseInfo["status_name"] == "Delete Case") { echo "selected"; }
                echo '>Delete Case</option>
                </select>
                </td>       </tr>';
      ?>
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
    <?php
      echo '<tr>
        <td><textarea aria-label="Notes (Required)" minlength="10" class="form-control" name="notes" rows="5">'.$rowCaseInfo["notes"].'</textarea>
        </td>
     </tr>';
    }
    $resultCaseInfo->free();
    ?>
  </tbody>
</table>
<button type="submit" class="btn btn-warning">Update Case Info</button>
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
           $field4name = $rowAssigned["self_dispatch"];
           echo '<tr>
             <td>'.$field1name.'</td>';
             if ($rowAssigned["dispatch"]==0 && $rowAssigned["support"]==0) {
               echo '<td>Primary Seal</td>';
             }
             elseif ($rowAssigned["dispatch"]==1 && $rowAssigned["support"]==0) {
               echo '<td>Dispatcher</td>';
             }
             elseif ($rowAssigned["dispatch"]==0 && $rowAssigned["support"]==1) {
               echo '<td>Supporting Seal</td>';
             }
             elseif ($rowAssigned["dispatch"]==1 && $rowAssigned["support"]==1) {
               echo '<td>Supporting Dispatcher</td>';
             }
             if ($rowAssigned["self_dispatch"]==0) {
               echo '<td>No</td>';
             }
             else {
               echo '<td>Yes</td>';
             }
          echo '</tr>';
         }
         $resultAssigned->free();
         ?>
       </tbody>
       </table>
     </table>
   <p><a href="fisher-review.php?cne=<?php echo $beingManaged; ?>" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p><hr>

     <br>
  </article>
  <div class="clearfix"></div>
</section>
</div>
<?php include '../assets/includes/footer.php'; ?>
</body>
</html>
