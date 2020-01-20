<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset="<?php echo $CHARSET; ?>">
	<title>Dynamic Properties Entry Aid</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>

  <style>
    img {
      cursor: pointer;
      width: 0.75em;
    }
  </style>

	<script type="text/javascript">
    // Global object representing the current state of the form
    const propertyObj = {};

    function onPropertyChanged(k, v) {
      if (k !== "") {
        propertyObj[k] = v;
      }
    }

    function getPropertyElement(key, val) {
      const newRow = document.createElement("tr");

      const newKeyCol = document.createElement("td");
      const newValCol = document.createElement("td");
      const newImgCol = document.createElement("td");

      const newKey = document.createElement("input");
      const newVal = document.createElement("input");
      const newImg = document.createElement("img");

      newKey.placeholder = "Property Key";
      newKey.value = key;

      newVal.placeholder = "Property Value";
      newVal.value = val;

      if (key !== "") {
        propertyObj[key] = val;
      }

      newImg.src = "../../images/del.png";
      newImg.alt = "delete row";
      newImg.onclick = () => {
        newRow.parentNode.removeChild(newRow);
        if (Object.keys(propertyObj).includes(newKey.value)) {
          delete propertyObj[newKey.value];
        }
      };

      newKey.onchange = () => onPropertyChanged(newKey.value, newVal.value);
      newVal.onchange = () => onPropertyChanged(newKey.value, newVal.value);

      newKeyCol.appendChild(newKey);
      newValCol.appendChild(newVal);
      newImgCol.appendChild(newImg);

      newRow.appendChild(newKeyCol);
      newRow.appendChild(newValCol);
      newRow.appendChild(newImgCol);

      return newRow;
    }

    function getEmptyPropertyElement() {
      return getPropertyElement("", "");
    }

    function addEmptyRow(newRowsId) {
      const newRowsContainer = document.getElementById(newRowsId);
      newRowsContainer.appendChild(getEmptyPropertyElement());
    }

		function submitForm() {
      opener.document.fullform.dynamicproperties.value = JSON.stringify(propertyObj);
      window.close();
    }
	</script>
</head>

<body style="background-color:white">
	<!-- This is inner text! -->
	<div id="innertext" style="background-color:white;">
		<fieldset style="width:450px;">
			<legend><b>Dynamic Properties Entry Aid</b></legend>
			<table id="props-tbl">
        <tbody id="existing-props"></tbody>
        <tbody id="new-props"></tbody>
			</table>
      <div style="margin-top: 1em;">
        <button id="submitButton" onclick="submitForm();">
          Update properties
        </button>
        <button id="addRowButton" onclick="addEmptyRow('new-props');">
          Add row
        </button>
      </div>
    </fieldset>
	</div>
  <script type="text/javascript">
    const origProps = opener.document.fullform.dynamicproperties.value;
    const existingPropsContainer = document.getElementById("existing-props");
    const newPropsContainer = document.getElementById("new-props");

    try {
      const origPropsJSON = JSON.parse(origProps);
      Object.keys(origPropsJSON).forEach((k) => {
        existingPropsContainer.appendChild(getPropertyElement(k, origPropsJSON[k]));
      });
    } catch(e) {
      if (origProps !== '') {
        existingPropsContainer.appendChild(getPropertyElement('notes', origProps));
      }
    }
    newPropsContainer.appendChild(getEmptyPropertyElement());
  </script>
</body>
</html> 

