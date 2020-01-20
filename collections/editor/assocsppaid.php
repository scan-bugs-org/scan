<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Associated Species Entry Aid</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">

		$(document).ready(function() {
		  const autocompleteOpts = { minLength: 2, autoFocus: true, delay: 200 };
		  const nameElem = $("#taxonname");
		  const typeElem = $("#associationType");
		  const defnElem = $("#associationTypeDefn");

			nameElem.autocomplete(
			  { source: "./rpc/getassocspp.php" },
			  autocompleteOpts
      );

			fetch("./rpc/getAssociationTypes.php").then((res) => {
        return res.json();
      }).catch((err) => {
        console.error(err);
      }).then((resJson) => {
        let types = Object.keys(resJson);

        typeElem.autocomplete(
          { source: types },
          autocompleteOpts
        );

        typeElem.blur(() => {
          let value = typeElem.val();
          if (types.includes(value)) {
            defnElem.html(`<a target="_blank" href="${resJson[value]}">Term definition</a>`);
          } else {
            defnElem.html("");
          }
        });
      });

			nameElem.focus();
		});

    // TODO: Push to dynamic props
		function addName(){
		    const nameElem = document.getElementById("taxonname");
		    const typeElem = document.getElementById("associationType");
		    let newAssociations = {};

		    if (nameElem.value) {
		    	let asStr = opener.document.fullform.associatedtaxa.value;
		    	if (asStr) {
		    	  try {
              newAssociations = JSON.parse(asStr);
            } catch (e) {
              newAssociations = { "interactsWith": [asStr] };
            }
          }

		    	if (typeElem.value) {
            newAssociations[typeElem.value] = nameElem.value;
          } else if (Object.keys(newAssociations).includes("unspecified")) {
		    	  newAssociations["unspecified"].push(nameElem.value);
          } else {
		    	  newAssociations["unspecified"] = [nameElem.value];
          }

		    	opener.document.fullform.associatedtaxa.value = JSON.stringify(newAssociations);

		    	typeElem.value = "";
		    	nameElem.value = "";
		    	nameElem.focus();
		    }
	    }

	</script>
</head>

<body style="background-color:white">
	<!-- This is inner text! -->
	<div id="innertext" style="background-color:white;">
		<fieldset style="width:450px;">
			<legend><b>Associated Species Entry Aid</b></legend>
			<table>
        <tbody>
          <tr>
            <td><label for="associationType" style="text-align: end;">Association Type:</label></td>
            <td><input id="associationType" type="text" placeholder="Optional"/></td>
            <td><small id="associationTypeDefn"></small></td>
          </tr>
          <tr>
            <td><label for="taxonname" style="text-align: end;">Taxon:</label></td>
            <td><input id="taxonname" type="text"/></td>
          </tr>
          <tr>
            <td><input id="transbutton" type="button" value="Add Name" onclick="addName();" /></td>
          </tr>
        </tbody>
			</table>
		</fieldset>
	</div>
</body>
</html> 

