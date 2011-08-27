<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecDatasetManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_POST["collid"];
$hPrefix = $_POST['lhprefix'];
$hMid = $_POST['lhmid'];
$hSuffix = $_POST['lhsuffix'];
$lFooter = $_POST['lfooter'];
$occIdArr = $_POST['occid'];
$rowsPerPage = $_POST['rpp'];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$labelManager = new SpecDatasetManager();
$labelManager->setCollId($collId);

$isEditor = 0;
$occArr = array();
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}
if($action == 'Export Label Data'){
	$labelManager->exportCsvFile();
}
else{
	?>

	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
		<head>
		    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
			<title><?php echo $defaultTitle; ?> Default Labels</title>
		    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
			<style type="text/css">
				body {font-family:arial,sans-serif}
				table {page-break-before:auto;page-break-inside:avoid;}
				table td {padding:15px;}
				p.printbreak {page-break-after:always;}
				.lheader {width:100%; text-align:center; font:bold 16px arial,sans-serif; margin-bottom:10px;}
				.family {width:100%;text-align:right;}
				.sciname {font-weight:bold;}
				.identifiedbydiv {margin-left:15px;}
				.identificationreferences {margin-left:15px;}
				.identificationremarks {margin-left:15px;}
				.country {font-weight:bold;}
				.stateprovince {font-weight:bold;}
				.county {font-weight:bold;}
				.associatedtaxa {font-style:italic;}
				.collectordiv {margin-top:10px;}
				.recordnumber {margin-left:10px;}
				.associatedcollectors {margin:0px 0px 10px 15px;clear:both;}
				.disposition {width:100%;text-alignment:center;font-size:90%;}
				.lfooter {width:100%; text-align:center; font:bold 14px arial,sans-serif; margin-bottom:10px;}
			</style>
		</head>
		<body>
			<div style="width:550pt;">
				<?php 
				if($isEditor){
					if($action){
						$rs = $labelManager->getLabelRecordSet($occIdArr);
						$labelCnt = 0;
						while($r = $rs->fetch_object()){
							$midStr = '';
							if($hMid == 1){
								$midStr = $r->country;
							}
							elseif($hMid == 2){
								$midStr = $r->stateprovince;
							}
							elseif($hMid == 3){
								$midStr = $r->county;
							}
							elseif($hMid == 4){
								$midStr = $r->family;
							}
							$headerStr = $hPrefix.$midStr.$hSuffix;
							
							$dupCnt = $_POST['q-'.$r->occid];
							for($i = 0;$i < $dupCnt;$i++){
								$labelCnt++;
								if($labelCnt%2) echo '<table><tr>'."\n";
								?>
								<td style="width:250pt;">
									<div class="lheader">
										<?php echo $headerStr; ?>
									</div>
									<?php if($hMid != 4) echo '<div class="family">'.$r->family.'</div>'; ?>
									<div>
										<?php 
										if($r->identificationqualifier) echo '<span class="identificationqualifier">'.$r->identificationqualifier.'</span> ';
										$scinameStr = $r->sciname;
										$scinameStr = str_replace(' subsp. ','</i> subsp. <i>',$scinameStr);
										$scinameStr = str_replace(' ssp. ','</i> ssp. <i>',$scinameStr);
										$scinameStr = str_replace(' var. ','</i> var. <i>',$scinameStr);
										?>
										<span class="sciname">
											<i><?php echo $scinameStr; ?></i>
										</span> 
										<span class="scientificnameauthorship"><?php echo $r->scientificnameauthorship; ?></span>
									</div>
									<?php 
									if($r->identifiedby){
										?>
										<div class="identifiedbydiv">
											<span class="identifiedby"><?php echo $r->identifiedby; ?></span> 
											<span class="dateidentified"><?php echo $r->dateidentified; ?></span>
										</div>
										<?php
										if($r->identificationreferences || $r->identificationremarks){
											?>
											<div class="identificationreferences">
												<?php echo $r->identificationreferences; ?>
											</div>
											<div class="identificationremarks">
												<?php echo $r->identificationremarks; ?>
											</div>
											<?php 
										}
									} 
									?>
									<div class="loc1div" style="margin-top:10px;">
										<span class="country"><?php echo $r->country.($r->country?', ':''); ?></span> 
										<span class="stateprovince"><?php echo $r->stateprovince.($r->stateprovince?', ':''); ?></span> 
										<span class="county"><?php echo $r->county.($r->county?', ':''); ?></span> 
										<span class="municipality"><?php echo $r->municipality.($r->municipality?', ':''); ?></span>
										<span class="locality"><?php echo $r->locality; ?></span> 
									</div>
									<?php
									if($r->decimallatitude){ 
										?>
										<div class="loc2div">
											<?php 
											echo $r->decimallatitude.' '.$r->decimallongitude.' ';
											if($r->coordinateuncertaintyinmeters) echo '+-'.$r->coordinateuncertaintyinmeters;
											if($r->geodeticdatum) echo ' ['.$r->geodeticdatum.']'; 
											?>
										</div>
										<?php
									}
									if($r->verbatimcoordinates){ 
										?>
										<div class="verbatimcoordinates">
											<?php echo $r->verbatimcoordinates; ?>
										</div>
										<?php
									}
									if($r->minimumelevationinmeters){ 
										?>
										<div id="elevdiv">
											Elev: 
											<?php 
											echo '<span class="minimumelevationinmeters">'.$r->minimumelevationinmeters.'</span>'.
											($r->maximumelevationinmeters?' - <span class="maximumelevationinmeters">'.$r->maximumelevationinmeters.'<span>':''),'m. ';
											if($r->verbatimelevation) '('.$r->verbatimelevation.')'; 
											?>
										</div>
										<?php
									}
									if($r->habitat){
										?>
										<div class="habitat"><?php echo $r->habitat; ?></div>
										<?php 
									}
									if($r->verbatimattributes || $r->establishmentmeans){
										?>
										<div>
											<span class="verbatimattributes"><?php echo $r->verbatimattributes; ?></span>
											<?php echo ($r->verbatimattributes?'. ':''); ?>
											<span class="establishmentmeans">
												<?php echo $r->establishmentmeans; ?>
											</span>
										</div>
										<?php 
									}
									if($r->associatedtaxa){
										?>
										<div>
											Associated species: 
											<span class="associatedtaxa"><?php echo $r->associatedtaxa; ?></span>
										</div>
										<?php 
									}
									if($r->occurrenceremarks){
										?>
										<div class="occurrenceremarks"><?php echo $r->occurrenceremarks; ?></div>
										<?php 
									}
									?>
									<div class=collectordiv>
										<div class="collectordiv1" style="float:left;">
											<span class="recordedby"><?php echo $r->recordedby; ?></span> 
											<span class="recordnumber"><?php echo $r->recordnumber; ?></span> 
										</div>
										<div class="collectordiv2" style="float:right;">
											<span class="eventdate"><?php echo $r->eventdate; ?></span>
										</div>
										<div class="associatedcollectors">
											<?php echo $r->associatedcollectors; ?>
										</div>
									</div>
									<?php
									if($r->disposition){ 
										?>
										<div class="disposition">
											<?php echo 'Duplicates to: '.$r->disposition; ?>
										</div>
										<?php
									} 
									?>
									<div class="lfooter">
										<?php echo $lFooter; ?>
									</div>
								</td> 
								<?php
								if($labelCnt%2 == 0){
									echo '</tr></table>'."\n";
									if($rowsPerPage && ($labelCnt/2)%$rowsPerPage == 0){
										echo '<p class="printbreak"></p>'."\n";
									}
								}
							}
						}
						if($labelCnt%2){
							echo '<td></td></tr></table>'; //If label count is odd, close final labelrowdiv
						} 
						$rs->close();
					}
				}
				?>
			</div>
		</body>
	</html>
	<?php 
}
?>