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

if (isset($_GET['del'])) {
    foreach ($_REQUEST as $key => $value) {
        $lore[$key] = strip_tags(stripslashes(str_replace(["'", '"'], '', $value)));
    }
      $stmt = $mysqli->prepare('CALL spDeleteCase(?,?,?,?)');
      $stmt->bind_param('iiss', $beingManaged, $user->data()->id, $lore['notes'], $lgd_ip);
      $stmt->execute();
      $stmt->close();
  header("Location: cases-list.php");
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
      <h2>Welcome, <?php echo echousername($user->data()->id); ?>.</h2>
      <p>You are Reviewing Paperwork for Case # <?php echo $beingManaged;?> <a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
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
        while ($rowCaseInfo = $resultCaseInfo->fetch_assoc()) {
          echo '<tr>
          <td>'.$rowCaseInfo["client_nm"].'</td>
          <td>'.$rowCaseInfo["current_sys"].'</td>
          <td>'.$rowCaseInfo["platform_name"].'</td>
          <td>'.$rowCaseInfo["case_created"].'</td>
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
          <th>Case Status</th>
      </tr>
    </thead>
    <tbody>
      <?php
        echo '<tr>';
        if ($rowCaseInfo["canopy_breach"]==0) {
          echo '<td>Intact</td>';
        }
        elseif ($rowCaseInfo["canopy_breach"]==1) {
          echo '<td>Broken</td>';
        }
        else {
          echo '<td>ERROR!</td>';
        }
        echo '<td>'.$rowCaseInfo["hull_stat"].'</td>
        <td>'.$rowCaseInfo["color_name"].'</td>
        <td>'.$rowCaseInfo["status_name"].'</td>
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
        <td>'.$rowCaseInfo["notes"].'</td>
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
       <?php if(hasPerm([7,8,9,10],$user->data()->id)){?>
         <h4>Cyberseal Access:</h4>
       <a href="case-edit.php?cne=<?php echo"$beingManaged" ?>" class="btn btn-small btn-warning">Edit This Case</a> <button class="btn btn-danger btn-small" data-target="#moDel" data-toggle="modal" type="button">Mark Case for Deletion</button>
	<div aria-hidden="true" class="modal fade" id="moDel" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel" style="color:black;">Mark Case for Deletion</h5><button aria-label="Close" class="close" data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body" style="color:black;">
					<form action="?del" method="post">
						<div class="input-group mb-3">
		            <textarea aria-label="Notes (Required)" class="form-control" name="notes" placeholder="Reason for Deletion (Required)" required rows="5" style="color:black;"><?= $data['notes'] ?? '' ?></textarea>
						</div>
						<div class="modal-footer">
							<button class="btn btn-primary" type="submit">Submit</button><button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
     <?php } else {?>
       <h4>To modify incorrect information, please <a class="btn btn-small btn-primary" href="mailto:cyberseals@hullseals.space?subject=Case%20Edit%20Request&body=Edit%20requested%20to%20case%20<?php echo"$beingManaged" ?>!" target="_blank">contact the CyberSeals.</a></h4>
     <?php }?>
       <br>
       <p><a href="cases-list.php" class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
       <br>
    </article>
    <div class="clearfix"></div>
</section>
</div>
<?php include '../assets/includes/footer.php'; ?>
</body>
</html>
