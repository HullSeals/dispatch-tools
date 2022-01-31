<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//UserSpice Required
require_once '../users/init.php';  //make sure this path is correct!
if (!securePage($_SERVER['PHP_SELF'])){die();}

$db = include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($db['server'], $db['user'], $db['pass'], 'records', $db['port']);

//Get All Paperwork

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta content="Welcome to the Hull Seals, Elite Dangerous's Premier Hull Repair Specialists!" name="description">
 <title>Paperwork List | The Hull Seals</title>
 <meta content="David Sangrey" name="author">
 <?php include '../assets/includes/headerCenter.php'; ?>
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" integrity="sha384-1CmrxMRARb6aLqgBO7yyAxTOQE2AKb9GfXnEo760AUcUmFx3ibVJJAzGytlQcNXd" crossorigin="anonymous"></script>
 <link rel="stylesheet" type="text/css" href="../assets/css/datatables.min.css"/>
 <script type="text/javascript" src="../assets/javascript/datatables.min.js"></script>
 <link rel="stylesheet" type="text/css" href="cssTableOverride.css" /><!--I don't know why this fixes the table, but hey, it does. ~ Rix-->
 <script>
 $(document).ready(function() {
 $('#PaperworkList').DataTable({
   "order": [[ 0, 'desc' ]]
 });
} );</script>
</head>
<body>
    <div id="home">
      <?php include '../assets/includes/menuCode.php';?>
      <section class="introduction container">
    <article id="intro3">
      <h2>Welcome, <?php echo echousername($user->data()->id); ?>.</h2>
      <p>Here is the review status of filed cases... <a href="." class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
      <br>
      <br>
      <table class="table table-hover table-dark table-responsive-md table-bordered table-striped" id="PaperworkList">
        <thead>
        <tr>
            <th>Case ID</th>
            <th>Client</th>
            <th>Seal</th>
            <th>Review Status</th>
            <th>Reviewer</th>
            <th>Date</th>
            <th>Options</th>
        </tr>
      </thead>
      <tbody>
        <?php
$stmt = $mysqli->prepare("WITH sealsCTI
AS
(
    SELECT MIN(ID), seal_ID, seal_name
    FROM sealsudb.staff
    GROUP BY seal_ID
)
SELECT c.case_ID, client_nm, case_created, hs_kf, rev_stat_text, COALESCE(ss.seal_name, (SELECT ss.seal_name FROM case_assigned WHERE case_stat != 8 AND (dispatch = TRUE AND support = FALSE AND c.case_ID = case_ID)), 'ERROR') AS seal_name, COALESCE(ss2.seal_name, CONCAT('SEAL ID', reviewer), 'Not Assigned') as reviewer
FROM cases AS c
    JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
    JOIN review_info as ri on ri.caseID = c.case_ID
    LEFT JOIN case_assigned AS ca ON ca.case_ID = c.case_ID
    LEFT JOIN sealsCTI AS ss ON ss.seal_ID = ca.seal_kf_id
    LEFT JOIN case_history AS ch ON ch.ch_id = c.last_ch_id
    JOIN lookups.review_stat_lu as rsl on rsl.rev_stat_ID = ri.review_status
    LEFT JOIN sealsCTI as ss2 on ss2.seal_ID = ri.reviewer
WHERE case_stat != 8 AND review_status != 3
GROUP BY c.case_ID DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $field1name = $row["case_ID"];
  $field2name = $row["client_nm"];
  $field3name = $row["rev_stat_text"];
  $field4name = $row["reviewer"];
  $field5name = $row["case_created"];
  $field6name = $row["seal_name"];
  echo '<tr>
    <td>'.$field1name.'</td>
    <td>'.$field2name.'</td>
    <td>'.$field6name.'</td>
    <td>'.$field3name.'</td>
    <td>'.$field4name.'</td>
    <td>'.$field5name.'</td>';
if ($row["hs_kf"]==2) {
  echo  '<td><a href="fisher-edit.php?cne='.$field1name.'" class="btn btn-info active">Review KF Case</a></td>';
}
else {
echo  '<td><a href="case-edit.php?cne='.$field1name.'" class="btn btn-warning active">Review Seal Case</a></td>';

}
  echo '</tr>';
}
$result->free();
?>

      </tbody>
      </table>
    </article>
    <div class="clearfix"></div>
</section>
</div>
<?php include '../assets/includes/footer.php'; ?>
</body>
</html>