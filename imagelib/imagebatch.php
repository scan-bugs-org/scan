<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ImageArchiveUploader.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID){
	header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../misc/imagebatch.php?'.$_SERVER['QUERY_STRING']);
}

// Get collections list
$collectionPermissionSql = <<< EOD
select distinct c.collid, c.collectionname
from omcollections c
inner join userroles r on c.collid = r.tablepk
inner join users u on r.uid = u.uid
where (r.role = 'CollEditor' or r.role = 'CollAdmin') and u.uid = $SYMB_UID
order by c.collectionname;
EOD;

$sqlConn = MySQLiConnectionFactory::getCon("readonly");
$allowedCollections = array();
if ($res = $sqlConn->query($collectionPermissionSql)) {
	while($coll = $res->fetch_assoc()) {
		array_push($allowedCollections, array("collid" => $coll['collid'], "collectionname" => $coll['collectionname']));
	}
	$res->close();
}
$sqlConn->close();

?>

<html>
	<head>
		<title>Batch Image Upload</title>
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
			function formValidate() {
				const form = document.forms["batchImage"];
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
				Images should be compressed into a zip file. Please do not upload
				unless directed to do so by the site administrator. Save the log files
				produced by this page in case you need to email them to the site
				administrator.
			</p>
			<h4>
				Currently, all image file names must start with the catalog number
				followed by an underscore. Catalog numbers that are not already
				associated with a record in the SCAN database will be rejected.
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
				Following the underscore, a unique identifying string
				must be used for each image. In the above example, the identifying
				string is a D, L, or V, for dorsal, lateral, or ventral.
			</p>
			<?php
			if (sizeof($allowedCollections) > 0) {
			?>
				<form
					name="batchImage"
					id="batchImage"
					class="container"
					onsubmit="return formValidate();"
					method="post"
					enctype="multipart/form-data"
					action=<?php echo $CLIENT_ROOT . "/imagelib/imagebatch.php"?>>
					<table>
						<tr>
							<td style="text-align: right;"><label for="collection">Collection:</label></td>
							<td>
								<select name="collection" required>
									<?php
										foreach ($allowedCollections as $coll) {
											echo '<option value="' . $coll["collid"] . '">' . $coll["collectionname"] . '</option>';
										}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style="text-align: right;"><label for="file">Image Archive:</label></td>
							<td><input type="file" name="file" required></td>
						</tr>
						<br>
						<tr>
							<td></td>
							<td style="text-align: right;">
								<input type="submit" value="Submit">
							</td>
						</tr>
					</table>
				</form>
			<?php
			} else {
			?>
				<b style="color: red">You aren't a collection editor. Contact your collection administrator.</b>
			<?php
			}
			?>

			<p>
			<?php
				if($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('file', $_FILES)) {

          $uploader = new ImageArchiveUploader($_POST['collection']);
					$uploader->load($_FILES['file']);

          $log = $uploader->getLogContent();
          echo "<pre>$log</pre>";

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
      </p>

			<a id="logfile" style="display: none;"></a>
			<script>
				const logFileDownloader = document.getElementById("logfile")
				logFileDownloader.href = "data:text/plain;charset=utf-8;base64," + "<?php echo base64_encode($log); ?>";
				logFileDownloader.download = "batchupload-" + Date.now() + ".log";
				logFileDownloader.click();
			</script>

		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
