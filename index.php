<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//Declare Title, Content, Author
$pgAuthor = "David Sangrey";
$pgContent = "Dispatcher Tools";
$useIP = 0; //1 if Yes, 0 if No.

//UserSpice Required
require_once '../users/init.php';  //make sure this path is correct!
require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
if (!securePage($_SERVER['PHP_SELF'])) {
  die();
}
?>
<div class="alert alert-info" role="alert">
  This Dashboard is a WORK IN PROGRESS and is subject to change without notice.<br><br> You've been warned!
</div>
<h1>Welcome, Dispatcher</h1>
<p>Please choose your option.</p>
<br>
<h3>Dispatcher Resources</h3>
<ul class="nav nav-pills nav-fill">
  <li class="nav-item">
    <a class="nav-link disabled" href="#">Active Cases</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active btn btn-success" href="delayed">Delayed Case Dashboard</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active btn btn-info" href="cases-list.php">Case Review</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active btn btn-warning" href="my-cases.php">View My Cases</a>
  </li>
</ul>
<?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>