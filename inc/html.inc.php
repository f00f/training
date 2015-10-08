<?php
////////////////////////////////////////////////////////////////////////////////
//// HTML Helper Functions ////
////////////////////////////////////////////////////////////////////////////////

//! Print page header for admin pages
function html_header() {
	global $pagetitle;
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- link rel="shortcut icon" href="../../assets/ico/favicon.png" -->

    <title><?= $pagetitle ?></title>

    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="admin.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="../js/html5shiv.js"></script>
      <script src="../js/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>
<?php
	if (@$_SESSION['error']) {
		print '<div class="container">'
			. "<div class='alert alert-danger'>{$_SESSION['error']}</div>"
			. '</div>';
		unset($_SESSION['error']);
	}
	if (@$_SESSION['warning']) {
		print '<div class="container">'
			. "<div class='alert alert-warning'>{$_SESSION['warning']}</div>"
			. '</div>';
		unset($_SESSION['warning']);
	}
	if (@$_SESSION['notice']) {
		print '<div class="container">'
			. "<div class='alert alert-success'>{$_SESSION['notice']}</div>"
			. '</div>';
		unset($_SESSION['notice']);
	}
} // html_header

//! Print navigation menu for admin pages
function navbar_admin($active = null) {
	global $pagetitle;
?>
    <div class="navbar navbar-fixed-top navbar-default">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><?= $pagetitle; ?></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav pull-right">
            <li<?=('home'==$active)?' class="active"':''?>><a href="./">Admin</a></li>
            <li><a href="../">T-Seite</a></li>
            <li class="dropdown<?=('players'==$active)?' active':''?>">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Spieler <b class="caret"></b></a>
			  <ul class="dropdown-menu">
			  <li><a href="players_list.php">Auflisten</a></li>
			  <li><a href="player_add.php">Hinzufügen</a></li>
			  </ul>
			</li>
            <li class="dropdown<?=('practice-times'==$active)?' active':''?>">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Zeiten <b class="caret"></b></a>
			  <ul class="dropdown-menu">
			  <li><a href="practice_times_list.php">Auflisten</a></li>
			  <li><a href="practice_time_add.php">Hinzufügen</a></li>
			  </ul>
			</li>
            <li class="dropdown<?=('etc'==$active)?' active':''?>">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Etc <b class="caret"></b></a>
			  <ul class="dropdown-menu">
				<li<?=('config'==$active)?' class="active"':''?>><a href="config_show.php">Konfig</a></li>
				<li<?=('stats'==$active)?' class="active"':''?>><a href="stats.php">Stats</a></li>
				<li<?=('contact'==$active)?' class="active"':''?>><a href="contact.php">Kontakt</a></li>
			  </ul>
			</li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

<?php
}

//! Print page footer for admin pages
function html_footer() {
	global $enablePopovers;
?>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="../js/jquery.js"></script>
    <script src="../js/bootstrap.min.js"></script>
	<?php
	if (count($enablePopovers) > 0) {
		$sels = array_keys($enablePopovers);
		print "<script>\n";
		foreach ($sels as $sel) {
			print "\$('{$sel}').popover({html:true});\n";
		}
		print "</script>\n";
	}
	?>
  </body>
</html>
<?php
} // html_footer
