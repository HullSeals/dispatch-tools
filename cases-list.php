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
      <p><a href="." class="btn btn-small btn-danger" style="float: right;">Go Back</a></p>
      <br>
      <br>
      <table class="table table-hover table-dark table-responsive-md table-bordered table-striped" id="PaperworkList">
        <thead>
        <tr>
            <th>Case ID</th>
            <th>Client</th>
            <th>System</th>
            <th>Platform</th>
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
SELECT c.case_ID, seal_name, client_nm, current_sys, platform_name, case_created
FROM sealsCTI AS cti
    JOIN case_assigned AS ca ON ca.seal_kf_id = cti.seal_ID
    JOIN cases AS c ON c.case_ID = ca.case_ID
    JOIN case_seal AS cs ON cs.case_ID = c.case_ID
    JOIN lookups.platform_lu AS plu ON plu.platform_id = c.platform
WHERE ca.dispatch IS FALSE AND ca.support IS FALSE");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $field1name = $row["case_ID"];
          $field2name = $row["client_nm"];
          $field3name = $row["current_sys"];
          $field4name = $row["platform_name"];
          $field5name = $row["case_created"];
          echo '<tr>
            <td>'.$field1name.'</td>
            <td>'.$field2name.'</td>
            <td>'.$field3name.'</td>
            <td>'.$field4name.'</td>
            <td>'.$field5name.'</td>
            <td><a href="case-review.php?cne='.$field1name.'" class="btn btn-warning active">Review Case</a></td>
          </tr>';
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
