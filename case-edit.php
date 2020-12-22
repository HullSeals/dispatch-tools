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

//DB Info
$db = include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($db['server'], $db['user'], $db['pass'], 'records', $db['port']);
$platformList = [];
$res = $mysqli->query('SELECT * FROM lookups.platform_lu ORDER BY platform_id');
while ($burgerking = $res->fetch_assoc())
{
    $platformList[$burgerking['platform_id']] = $burgerking['platform_name'];
}

//All Case Info
$stmtCaseInfo = $mysqli->prepare("SELECT client_nm, canopy_breach, current_sys, platform_name,
    hull_stat, status_name, color_name, notes, case_created
FROM cases AS c
    JOIN case_seal AS cs ON cs.case_ID = c.case_ID
    JOIN case_history AS ch ON ch.ch_ID = c.last_ch_id
    JOIN lookups.status_lu AS slu ON slu.status_id = ch.case_stat
    JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
    JOIN lookups.case_color_lu AS ccl ON ccl.color_id = ch.code_color
WHERE c.case_ID = ?;");
$stmtCaseInfo->bind_param("i", $beingManaged);
$stmtCaseInfo->execute();
$resultCaseInfo = $stmtCaseInfo->get_result();
$stmtCaseInfo->close();
//$rowCaseInfo = $resultCaseInfo->fetch_assoc();
if($resultCaseInfo->num_rows === 0) {
  Redirect::to('cases-list.php');
}
//All Assigned Seals
$stmtAssigned = $mysqli->prepare("WITH sealsCTI
AS
(
    SELECT MIN(ID), seal_ID, seal_name
    FROM sealsudb.staff
    GROUP BY seal_ID
)
SELECT seal_name, dispatch, support, self_dispatch
FROM case_assigned AS ca
    JOIN sealsCTI AS ss ON ss.seal_ID = ca.seal_kf_id
WHERE case_ID = ?;");
$stmtAssigned->bind_param("i", $beingManaged);
$stmtAssigned->execute();
$resultAssigned = $stmtAssigned->get_result();
$stmtAssigned->close();
//$rowAssigned = $resultAssigned->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta content="Welcome to the Hull Seals, Elite Dangerous's Premier Hull Repair Specialists!" name="description">
 <title>Case Edit | The Hull Seals</title>
 <meta content="David Sangrey" name="author">
 <?php include '../assets/includes/headerCenter.php'; ?>
</head>
<body>
    <div id="home">
      <?php include '../assets/includes/menuCode.php';?>
      <section class="introduction container">
    <article id="intro3">
      <form action="?send" method="post">
      <h2>Welcome, <?php echo echousername($user->data()->id); ?>.</h2>
      <p>You are Editing Paperwork for Case # <?php echo $beingManaged;?> <a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
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
          <td><select class="custom-select" id="inputGroupSelect01" name="platypus" required="">
          <option value="1"';
          if ($rowCaseInfo["platform_name"] == "PC") { echo "selected"; }
          echo '>PC</option>
          <option value="2"';
          if ($rowCaseInfo["platform_name"] == "XB1") { echo "selected"; }
          echo '>Xbox</option>
          <option value="3"';
          if ($rowCaseInfo["platform_name"] == "PS4") { echo "selected"; }
          echo '>PlayStation</option>
          </select>
          </td>
         </tr>';
        ?>
      </tbody>
    </table>
    <br>
    <h5>Situation Information</h5>
    <table class="table table-hover table-dark table-responsive-md table-bordered table-striped">
      <thead>
      <tr>
          <th>Canopy Status</th>
          <th>Hull Status</th>
          <th>Case Color</th>
      </tr>
    </thead>
    <tbody>
      <?php
        echo '<tr>
        <td><select class="custom-select" id="inputGroupSelect01" name="canopy_status" required="">';
        if ($rowCaseInfo["canopy_breach"]==0) {
          echo '<option value="0" selected>Intact</option>
          <option value="1">Broken</option>';
        }
        elseif ($rowCaseInfo["canopy_breach"]==1) {
          echo '<option value="0">Intact</option>
          <option value="1" selected>Broken</option>';
        }
        else {
          echo '<option value="0">Intact</option>
          <option value="1">Broken</option>';
        }
        echo '</select>
        </td>
        <td><input aria-label="Starting Hull %" class="form-control" max="100" min="1" name="hull" placeholder="Starting Hull %" required="" type="number" value="'.$rowCaseInfo["hull_stat"].'"></td>
        <td>
        <select class="custom-select" id="inputGroupSelect01" name="color" required="">
        <option value="1"';
        if ($rowCaseInfo["color_name"] == "Green") { echo "selected"; }
        echo '>Green</option>l
        <option value="2"';
        if ($rowCaseInfo["color_name"] == "Amber") { echo "selected"; }
        echo '>Amber</option>
        <option value="3"';
        if ($rowCaseInfo["color_name"] == "Red") { echo "selected"; }
        echo '>Red</option>
        <option value="4"';
        if ($rowCaseInfo["color_name"] == "Black") { echo "selected"; }
        echo '>Black</option>
        <option value="5"';
        if ($rowCaseInfo["color_name"] == "Blue") { echo "selected"; }
        echo '>Blue</option>
        <option value="6"';
        if ($rowCaseInfo["color_name"] == "Teal") { echo "selected"; }
        echo '>Teal</option>
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
       </tr>';
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
        <td><textarea aria-label="Notes (Required)" minlength="10" class="form-control" name="notes" placeholder="Notes (Required).
          Suggested notes include:
          - Distance Traveled
          - Unique or Unusual details about the repair
          - Number of Limpets used, Client Ship Type, or other details." rows="5">'.$rowCaseInfo["notes"].'
          </textarea>
        </td>
     </tr>';
    }
    $resultCaseInfo->free();
    ?>
  </tbody>
</table>
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
     </form>
     <p><button class="btn btn-primary" type="submit">Submit</button><a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p><hr>

     <?php if(hasPerm([9,10],$user->data()->id)){?>
                <hr><br><h3>Case Deletion</h3>
                <p>
<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#staticBackdrop">
  Delete This Case
</button>

<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="staticBackdropLabel" style="color:black;">Delete This Case</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="color:black;">
        Yes, I really want to delete this case from the Database.
      </div>
      <div class="modal-footer">
        <form action="?del" method="post">
          <input type="hidden" name="numberedt" value=<?php echo"$beingManaged" ?> required>
        	 <button type="submit" class="btn btn-danger">Yes, Remove.</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </form>
      </div>
    </div>
  </div>
</div>
</p>
              <?php }?>
       <br>
    </article>
    <div class="clearfix"></div>
</section>
</div>
<?php include '../assets/includes/footer.php'; ?>
</body>
</html>
