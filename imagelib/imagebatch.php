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
				Use this page to batch upload images for a particular collection.
				Images should jpegs, pngs, or gifs and compressed into a zip file.
				If you experience problems, save the log output from this page and
				email it to the site administrator.
			</p>
			<h4>
        This utility requires that each image file contains the associated
        catalog number, and that regular expressions be in the
          <a target="_blank" href="https://perldoc.perl.org/perlre.html">
            PCRE (Perl) format
          </a>.
        Catalog numbers that are not already associated with a record in the
        SCAN database will be rejected.
			</h4>
			<p>
				For example, to upload images for the catalog number NAUF4A0007000, the
				following files could be compressed into a zip archive and uploaded:
			</p>
			<ul>
				<li>NAUF4A0007000_D.jpg</li>
				<li>NAUF4A0007000_L.jpg</li>
				<li>NAUF4A0007000_V.jpg</li>
			</ul>
			<p>
				And the regex to detect the catalog number would be
        <a target="_blank" href="https://regex101.com/r/3mGb8E/1">
          NAUF\d[A-Z]\d{7}
        </a>.
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
								<select id="select-collection" name="collection" required disabled></select>
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
            </tr>
            <td>Catalog number regex:</td>
            <td>
              <input
                type="text"
                name="regex"
                required
              >
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
				if($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('file', $_FILES) && array_key_exists("regex", $_POST)) {

          $uploader = new ImageArchiveUploader($_POST['collection']);
					$uploader->load($_FILES['file'], '/' . trim($_POST['regex'], '/') . '/');

          $log = $uploader->getLogContent();
          echo "<pre>$log</pre>";

					if (array_key_exists('SOLR_MODE', $GLOBALS) && $GLOBALS['SOLR_MODE']) {
							$solrMgr = new SOLRManager();
							$solrMgr->updateSOLR();
					}

					// Validation
					// $result = "<li><b>Invalid zip archive!</b></li>";
					// $zipFileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
					// $zipMimeType = $_FILES['file']['type'];
					// $zipBasename = basename($_FILES['file']['name']);
					// $zipTypes = array(
					// 	'application/zip',
					// 	'application/x-zip-compressed',
					// 	'multipart/x-zip',
					// 	'application/x-compressed'
					// );
					// echo "<li>Uploading $zipBasename...</li>";
          //
					// if ($zipFileExt === 'zip' && in_array($zipMimeType, $zipTypes)) {
					// 	$targetDir = $SERVER_ROOT . '/temp/images';
					// 	$zipLocal = $targetDir . '/' . $zipBasename;
          //
					// 	move_uploaded_file($_FILES['file']['tmp_name'], $zipLocal);
					// 	$zip = new ZipArchive();
					// 	$zipSuccess = $zip->open($zipLocal, ZipArchive::CHECKCONS) !== ZipArchive::ER_NOZIP;
          //
					// 	if ($zipSuccess) {
					// 		// Process files
					// 		echo "<li><ul>";
					// 		for ($i = 0; $i < $zip->numFiles; $i++) {
					// 			// Extract file
					// 			$zipMemberName = $zip->getNameIndex($i);
					// 			echo "<li>Extracting " . $zipMemberName . "...</li>";
					// 			$zip->extractTo($targetDir, $zipMemberName);
					// 			$zipMemberName = basename($zipMemberName);
          //
					// 			// Extract catalogNumber
					// 			echo "<li>Processing " . $zipMemberName . "...<ul>";
					// 			$collId = $_POST['collection'];
					// 			$catalogNumber = explode("_", $zipMemberName)[0];
					// 			echo "<li>Catalog number is $catalogNumber</li>";
          //
					// 			$catalogSearchSql = "SELECT occid, sciname FROM omoccurrences where catalogNumber = '$catalogNumber' AND collid = $collId";
          //
					// 			// Find existing occurrence
					// 			$assocOccurrence = null;
					// 			$sqlConn = MySQLiConnectionFactory::getCon("readonly");
					// 			if ($res = $sqlConn->query($catalogSearchSql)) {
					// 				$assocOccurrence = $res->fetch_assoc();
					// 				$res->close();
					// 			}
					// 			$sqlConn->close();
          //
					// 			// Insert a skeleton occurrence
					// 			if ($assocOccurrence === null) {
					// 				echo "<li>Catalog number not found. Creating skeleton occurrence...</li>";
					// 			}
					// 			// Just print a status message
					// 			else {
					// 				echo "<li>Found associated occurrence: " . $assocOccurrence["sciname"] . "</li>";
					// 			}
          //
					// 			echo "</ul></li>";
					// 		}
					// 		echo "</ul></li>";
          //
					// 		// Clean up
					// 		$zip->close();
					// 		unlink($zipLocal);
					// 		$result = "<li><b>Success!</b></li>";
					// 	}
					// }
          //
					// echo $result;
				}
			?>

		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
