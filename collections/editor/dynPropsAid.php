<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
require_once "$SERVER_ROOT/config/twig.php";
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset="<?php echo isset($charset) ? $charset : "utf-8"; ?>">
	<title>Dynamic Properties Entry Aid</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
</head>

<body style="background-color:white">
	<!-- This is inner text! -->
	<div id="innertext" style="background-color:white;">
    <?php
      echo $twig->render("jsonEditor.twig", [
        "legend" =>  (defined('DYNAMICPROPERTIESLABEL') ? DYNAMICPROPERTIESLABEL : 'Dynamic Properties') . " Editor",
        "origFormName" => "dynamicproperties",
        "defaultPropName" => "notes",
        "keyPlaceholder" => "Property Key",
        "valuePlaceholder" => "Property Value"
      ]);
    ?>
	</div>
</body>
</html> 

