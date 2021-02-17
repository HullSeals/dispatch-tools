<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../users/init.php';  //make sure this path is correct!
if (!securePage($_SERVER['PHP_SELF'])){die();}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '../assets/includes/headerCenter.php'; ?>
    <title>Dispatcher Dashboard | The Hull Seals</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
</head>
<body>
    <div id="home">
      <?php include '../assets/includes/menuCode.php';?>
        <section class="introduction container">
	    <article id="intro3">
        <div class="alert alert-info" role="alert">
          This Dashboard is a WORK IN PROGRESS and is subject to change without notice.<br><br> You've been warned!
        </div>
        <h1>Welcome, Dispatcher</h1>
        <p>Please choose your option.</p>
        <br>
        <h3>Dispatcher Resources</h3>
      <div class="btn-group" style="display:flex;" role="group">
        <a href="active.php" class="btn btn-lg btn-primary disabled" style="max-width: 20%;">Active Cases</a>
        <button type="button" class="btn btn-secondary disabled" style="max-width: 20%;"></button>
        <a href="delayed" class="btn btn-lg btn-primary" style="max-width: 20%;">Delayed Cases</a>
        <button type="button" class="btn btn-secondary disabled" style="max-width: 20%;"></button>
        <a href="cases-list.php" class="btn btn-lg btn-primary" style="max-width: 20%;">Case Review</a>
      </div>
      </article>
            <div class="clearfix"></div>
        </section>
    </div>
    <?php include '../assets/includes/footer.php'; ?>
</body>
</html>
