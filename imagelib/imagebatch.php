<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ImageArchiveUploader.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID){
	header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=' . $CLIENT_ROOT . '/imagelib/imagebatch.php?'.$_SERVER['QUERY_STRING']);
}
?>

<html>
	<head>
		<title>Batch Image Upload</title>
    <meta charset='<?php echo isset($GLOBALS["CHARSET"]) ? $GLOBALS["CHARSET"] : "utf-8" ?>'>
		<link
			href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>"
			type="text/css"
			rel="stylesheet"/>
		<link
			href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>"
			type="text/css"
			rel="stylesheet" />
		<link
			href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css"
			type="text/css"
			rel="stylesheet" />
		<script
			src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js"
			type="text/javascript">
		</script>
		<script
			src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js"
			type="text/javascript">
		</script>
		<script
			src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js?ver=140310"
			type="text/javascript">
		</script>
		<script type="text/javascript">
      function httpGet(url) {
        const req = new XMLHttpRequest();
        return new Promise((resolve, reject) => {
          req.onreadystatechange = () => {
            if (req.readyState === 4 && req.status === 200) {
              resolve(req.responseText);
              req.onreadystatechange = () => {};
            } else if (req.readyState === 4) {
              reject(req.status);
            }
          };

          req.open("GET", url, true);
          req.send();
        });
      }

      function disableFormAndShowError(msg) {
        const batchImageForm = document.getElementById("batchImage");
        const submitButton = document.getElementById("submit-button");
        submitButton.setAttribute("disabled", "true");

        let errorMsg = document.createElement("p");
        errorMsg.style.color = "red";
        errorMsg.innerHTML = msg;
        batchImageForm.appendChild(errorMsg);
      }

			function onFormSubmit(form) {
				if (!form["file"].value.endsWith(".zip")) {
					alert("Invalid file format. Upload a zip file");
					form["file"].value = '';
					return false;
				}
        return true;
			}
		</script>
		<style>
			form {
				max-width: 50%;
			}

			td {
				padding-left: 1em;
				padding-right: 1em;
			}

			ul {
				list-style: none;
			}

      pre {
        white-space: pre-wrap;
        word-wrap: break-word;
      }
		</style>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>Batch Image Upload</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
			<h4>
				Warning: This page is currently in BETA testing and may produce
				unexpected results.
			</h4>
			<p>
				The batch upload image module can only be used by individuals that have editing rights for their respective collection. 
				Images must be jpegs, pngs, or gifs and compressed into a zip file. The zip file cannot be over 1GB. Each image should 
				be less than 3mb.  Please upload in the evenings or weekends if you have more than 100 images in a zip file. 
				If you experience problems, save the log output from this page and email it to evin@scan-bugs.org. 
			</p>
      <p>
        Each image filename must include the DwC <strong>catalogNumber</strong> that is compliant with one of the formats we have on record 
        for your collection or it will not be linked to the correct record. The file name should start with the <strong>catalogNumber</strong>
        followed by an underscore and whatever other codes or words you want to add (See example of additional codes that 
        provide information about the image https://scan-all-bugs.org/?page_id=43).
      </p>
      <h4>
        Currently, a skeletal record will not be created. We will implement this option on a need-only basis.
      </h4>
      <p>
        We have created a table of <strong>catalogNumber</strong> formats for each collection based on what was available 
        as of March 12, 2020. If you want to add a new format please contact evin@scan-bugs.org. <strong>catalogNumber</strong> 
        formats that are not already associated with a record in the SCAN database will be rejected, even if you are 
        establishing a new skeletal record with an image using the <strong>catalogNumber</strong> in the name of the file image.
      </p>
			<p>
        For example, to upload images for the catalog number NAUF4A0007000, the following files could be compressed
        into a zip archive and uploaded. The letters following the underscore indicate Dorsal, Lateral and
        Ventral and are ignored:
			</p>
			<ul>
				<li>NAUF4A0007000_D.jpg</li>
				<li>NAUF4A0007000_L.jpg</li>
				<li>NAUF4A0007000_V.jpg</li>
			</ul>
      <p>
        In the log output two numbers will be referenced, the occurrence number (i.e., Symbiota ID in editor display table)
        and the collection <strong>catalogNumber</strong>. Please make sure that the <strong>catalogNumber</strong> in the 
        record is printed on the specimen label in the exact same format. Otherwise there could be uncertainty in matching 
        a specimen record with a specimen. For example NAUF4A0007000 could be interpreted differently than NAUF 4 A 0007000 or NAUF4A7000
      </p>
      <form
        name="batchImage"
        id="batchImage"
        class="container"
        onsubmit="return onFormSubmit(this);"
        method="post"
        enctype="multipart/form-data"
        action=<?php echo $CLIENT_ROOT . "/imagelib/imagebatch.php"?>>
        <table>
          <tr>
            <td style="text-align: right;"><label for="collection">Collection:</label></td>
            <td>
              <select id="select-collection" name="collid" required disabled></select>
              <script>
                const selectCollection = document.getElementById("select-collection");
                let allowedCollections = [];
                httpGet("./rpc/imagebatch.php?allowedCollections=true")
                  .then((res) => {
                    allowedCollections = JSON.parse(res);

                    if (allowedCollections.length > 0) {
                      for (let i in allowedCollections) {
                        let currentItem = allowedCollections[i];
                        let selectItem = document.createElement("option");
                        selectItem.innerHTML = currentItem.collectionname;
                        selectItem.value = currentItem.collid;
                        selectCollection.appendChild(selectItem);
                      }
                      selectCollection.removeAttribute("disabled");
                    } else {
                      let errorMsg = "You aren't an editor on any collections. ";
                      errorMsg += "Contact your collection administrator.";
                      disableFormAndShowError(errorMsg);
                    }
                  })
                  .catch((err) => {
                    console.error(err);
                  });
              </script>
            </td>
          </tr>
          <tr>
            <td style="text-align: right;"><label for="file">Image Archive:</label></td>
            <td><input type="file" name="file" required></td>
          <br>
          <tr>
            <td></td>
            <td style="text-align: right;">
              <input id="submit-button" type="submit" value="Submit">
            </td>
          </tr>
        </table>
      </form>

			<p>
			<?php
				if($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('file', $_FILES) && array_key_exists("collid", $_POST)) {
          $uploader = new ImageArchiveUploader($_POST['collid']);
          $uploader->load($_FILES['file']);

          $log = $uploader->getLogContent();
          echo "<pre>$log</pre>";

          if (array_key_exists('SOLR_MODE', $GLOBALS) && $GLOBALS['SOLR_MODE']) {
              $solrMgr = new SOLRManager();
              $solrMgr->updateSOLR();
          }
        }
			?>

		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
