<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../users/init.php';  //make sure this path is correct!
if (!securePage($_SERVER['PHP_SELF'])){die();}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '../../assets/includes/headerCenter.php'; ?>
    <title>Dev Dispatcher Dashboard | The Hull Seals</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tokenfield/0.12.0/css/bootstrap-tokenfield.min.css">
      <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
      <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tokenfield/0.12.0/bootstrap-tokenfield.js"></script>
      <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tokenfield/0.12.0/css/bootstrap-tokenfield.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
    <div id="home">
      <?php include '../../assets/includes/menuCode.php';?>
        <section class="introduction container" style="max-width:80%;">
	    <article id="intro3" style="margin:0rem;">
        <h1>Dispatcher's Notepad</h1>
      <table class="table table-hover table-dark table-responsive-md table-bordered table-striped" id="DBoard">
        <thead>
        <tr>
            <th>Client</th>
            <th>Platform</th>
            <th>Hull</th>
            <th>Special Conditions?</th>
            <th>Seals Assigned</th>
            <th>FR</th>
            <th>WR</th>
            <th>BC</th>
            <th>RR</th>
            <th>Notes</th>
            <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr class="tr_clone">
          <td><input class="form-control" type="text" value="" id="cName"></td>
          <td>
            <select id="plt" class="form-control">
              <option selected></option>
              <option>PC</option>
              <option>PS</option>
              <option>XB</option>
            </select>
          </td>
          <td><input class="form-control" type="number" max="100" min="0" value="" id="hull"></td>
          <td>
            <select id="plt" class="form-control">
              <option selected></option>
              <option>Canopy Breach</option>
              <option>Caustic</option>
              <option>Code Blue</option>
            </select>
          </td>
          <td>
            <input class="form-control" id="aSeals" type="text" value="">
          </td>
          <td><input type="checkbox" value="" id="FR"></td>
          <td><input type="checkbox" value="" id="WR"></td>
          <td><input type="checkbox" value="" id="BC"></td>
          <td><input type="checkbox" value="" id="RR"></td>
          <td><textarea class="form-control" value="" id="notes" rows="2"></textarea></td>
          <td><button type="button" id="addRow" class="btn btn-outline-success">Add Row</button><br><button type="button" id="remRow" class="btn btn-outline-danger">Rem Row</button></td>
        </tr>
      </tbody>
      </table>
      <button type="button" class="btn btn-outline-secondary" data-toggle="modal" id="coord-help-button" data-target="#coordsHelp">
		                 What is this?
	               </button>

                 <div class="modal fade" id="coordsHelp" tabindex="-1" aria-hidden="true" style="color:#323232">
             <div class="modal-dialog modal-lg">
               <div class="modal-content">
                 <div class="modal-header">
                   <h5 class="modal-title" id="coordsHelpLabel">What is this?</h5>
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                   </button>
                 </div>
                 <div class="modal-body">
                   <p style="text-align: center;">Welcome to the Hull Seals Dispatch Board (v0)! This board is an easy way for Dispatchers to keep track of repairs, clients, conditions, and what key milestones of the repair have been met. <br><br>
                   In the future, this will become a fully-fledged webtool, complete with synced and saved cases across multiple clients, the ability to expedite paperwork, and lots of other fun features. Until then, enjoy using this tool and we hope it is of use to you!</p>
                 </div>
                 <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                 </div>
               </div>
             </div>
           </div>
      </article>
            <div class="clearfix"></div>
        </section>
    </div>
    <?php include '../../assets/includes/footer.php'; ?>
</body>
</html>
<script type="text/javascript">
//$('#aSeals').tokenfield({autocomplete: {source: function (request, response) {jQuery.get("fetch.php", {query: request.term}, function (data) {data = $.parseJSON(data);response(data);});},delay: 100},});
</script>
<script type="text/javascript">
$('#DBoard').on('click', '#addRow', function() {
    var $tr    = $(this).closest('.tr_clone');
    var $clone = $tr.clone();
    $clone.find('input').val('');
    $clone.find('option').val('');
    $clone.find('textarea').val('');
    $tr.after($clone);
});</script>
<script type="text/javascript">
$('#DBoard').on('click', '#remRow', function() {
    var $tr    = $(this).closest('.tr_clone');
    if( $(this).closest('.tr_clone').is('tr:only-child') ) {
    alert('Can\'t delete the only row! Clearing instead...');
    $tr.find('input').val('');
    $tr.find('option').val('');
    $tr.find('select').val('');
    $tr.find('textarea').val('');
}
else {
    var $clone = $tr.remove();
    $clone.find(':text').val('');
    $tr.after($clone);
  }
});</script>
