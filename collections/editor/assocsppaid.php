<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
require_once("$SERVER_ROOT/config/twig.php");
?>
<html lang="en">
<head>
	<meta charset="<?php echo (isset($charset) ? $charset : "utf-8"); ?>">
	<title>Associated Species Entry Aid</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">

   function getAddOnElem(href) {
     return `
       <small class="addOn" style="font-size: 0.5rem">
         <a href="${href}" target="_blank">Term definition</a>
       </small>
     `;
   }

		$(document).ready(function() {
		  const keyElems = $(".key");

      fetch("./rpc/getAssociationTypes.php").then((res) => {
        return res.json();
      }).catch((err) => {
       console.error(err);
      }).then((resJson) => {
        let types = Object.keys(resJson);

        keyElems.autocomplete(
          { source: types },
          autocompleteOpts
        );

        // keyElems.each(() => {
        //   $(this).blur(() => {
        //     let value = $(this).val();
        //     console.log($(this).siblings(".addOn"));
        //     if (types.includes(value)) {
        //       $(this).after(getAddOnElem(resJson[value]));
        //     }
        //   });
        // });
      });
		});
	</script>
</head>

<body style="background-color:white">
	<!-- This is inner text! -->
	<div id="innertext" style="background-color:white;">
    <?php
    echo $twig->render("assocSpecEditor.twig", [
      "legend" =>  (defined('ASSOCIATEDTAXALABEL') ? ASSOCIATEDTAXALABEL: 'Associated Taxa' ) . ' Editor',
      "origFormName" => "associatedtaxa",
      "defaultPropName" => "interactsWith",
      "keyPlaceholder" => "Association Type",
      "valuePlaceholder" => "Associated Taxa",
      "valAutocomplete" => "./rpc/getassocspp.php"
    ]);
    ?>
	</div>
</body>
</html> 

