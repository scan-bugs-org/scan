<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID){
	header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../misc/imagebatch.php?'.$_SERVER['QUERY_STRING']);
}

// Get collections list
$collList = array();
$sql = <<< EOD
select distinct c.collid, c.collectionname
from omcollections c
inner join userroles r on c.collid = r.tablepk
inner join users u on r.uid = u.uid
where (r.role = 'CollEditor' or r.role = 'CollAdmin') and u.uid = $SYMB_UID
order by c.collectionname;
EOD;

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
			<p>
				Use this page to batch upload images for a particular collection.
				Images should be compressed into a zip file.
			</p>
			<b>
				<p>
					Warning: This page is currently for testing only. Please do not upload
					unless directed to do so by the site administrator.
				</p>
			</b>
			<form
				name="batchImage"
				id="batchImage"
				class="container"
				onsubmit="return formValidate();"
				method="post"
				enctype="multipart/form-data"
				action=<?php echo $CLIENT_ROOT . "/misc/imagebatch.php"?>>
				<table>
					<tr>
						<td style="text-align: right;"><label for="collection">Collection:</label></td>
						<td>
							<select name="collection" required>
								<?php
									$sqlConn = MySQLiConnectionFactory::getCon("readonly");
									if ($res = $sqlConn->query($sql)) {
										while($coll = $res->fetch_assoc()) {
											echo '<option value="' . $coll['collid'] .'">' . $coll['collectionname'] . '</option>';
										}
										$res->close();
									}
									$sqlConn->close();
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
						<td></td><td style="text-align: right;"><input type="submit" value="Submit"></td>
					</tr>
				</table>
			</form>

			<ul>
			<?php
				// Validation
				$zipTypes = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
				$zipFileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
				$zipMimeType = $_FILES['file']['type'];
				$zipBasename = basename($_FILES['file']['name']);

				if($_SERVER['REQUEST_METHOD'] === 'POST' && $zipFileExt === 'zip' && in_array($zipMimeType, $zipTypes)) {
					$targetDir = $SERVER_ROOT . '/temp/images';
					$zipLocal = $targetDir . '/' . $zipBasename;

					echo "<li>Uploading $zipBasename...</li>";
					move_uploaded_file($_FILES['file']['tmp_name'], $zipLocal);
					$zip = new ZipArchive();
					$zipSuccess = $zip->open($zipLocal);
					$uploadedFiles = array();

					if ($zipSuccess) {

						// List archive's files
						echo "<li>Found Files:<ul>";
						for ($i = 0; $i < $zip->numFiles; $i++) {
							$zipMemberName = basename($zip->getNameIndex($i));
							echo "<li>" . $zipMemberName . "</li>";
							array_push($uploadedFiles, $targetDir . '/' . $zipMemberName);
						}
						echo "</ul></li>";

						// Extract files
						$zip->extractTo($targetDir);
						$zip->close();
						unlink($zipLocal);

						// TODO: Process Images
						foreach ($uploadedFiles as $imgFile) {
							echo "<li>Processing " . $imgFile . "...</li>";
							unlink($imgFile);
						}

						echo "<li><b>Success!</b></li>";

					} else {

						echo "<li><b>Invalid zip archive!</b></li>";

					}
				}
			?>
			</ul>
		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
