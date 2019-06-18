<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Home</title>
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION; ?>8" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once('config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($serverRoot."/header.php");
	?>
        <!-- This is inner text! -->
        <div  id="innertext">
            <h1>Symbiota Collections of Arthropods Network (SCAN): A Data Portal Built to Visualize, Manipulate, and Export Species Occurrences</h1>
            <div style="float:right;width:380px;">
                <div style="clear:both;float:right;width:320px;float:right;margin-right:8px;padding:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:1px solid black;" >
                    <div style="float:left;width:350px;">
                        <?php
                        $searchText = 'Taxon Search';
                        $buttonText = 'Search';
                        include_once($serverRoot.'/classes/PluginsManager.php');
                        $pluginManager = new PluginsManager();
                        $quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
                        echo $quicksearch;
                        ?>
                    </div>
                </div>
                <div style="float:right;margin-top:15px;margin-bottom:15px;width:350px;text-align:center;">
                    <div style="">
                        <?php
                        $ssId = 1;
                        $numSlides = 10;
                        $width = 315;
                        $dayInterval = 1;
                        $clId = "";
                        $imageType = "field";
                        $numDays = 365;
                        ini_set('max_execution_time', 120);
                        include_once($serverRoot.'/classes/PluginsManager.php');
                        $pluginManager = new PluginsManager();
                        $slideshow = $pluginManager->createSlidewhow($ssId,$numSlides,$width,$numDays,$imageType,$clId,$dayInterval);
                        echo $slideshow;
                        ?>
                    </div>
                </div>
            </div>
            <div style="padding: 0px 10px;">
                The Symbiota Collections of Arthropods Network (SCAN) serves specimen occurrence records and images from over
                100 North American arthropod collections for <b>all</b> arthropod taxa. The focus is on North America but global in scope.
                SCAN is built on <a href="http://symbiota.org/docs/" target="_blank">Symbiota</a>, a web-based collections database system that is used for other taxonomic data portals,
                including (Symbiota Portals). SCAN is the primary repository for occurrence data produced by the three continuing
                Thematic Collections Networks (TCNs), the Southwest Collections of Arthropods Network (SCAN TCN), the Lepidoptera
                of North America Network (LepNet TCN), and arthropod data produced by <a href="http://invertebase.org/portal/index.php" target="_blank">InvertEBase</a> TCN. InvertEBase serves occurrence
                data for mollusk and other non-arthropod taxa. We also host observational data, the largest data provider is iNaturalist.
                Each collection is primarily responsible for their data and we have structured the database to make it easy to include
                collections of interest when querying the database.
            </div>
            <div style="margin-top:10px;padding: 0px 10px;">
            	<b>Important features of all Symbiota portals include:</b>
				<ol start="1" type="1">
					<li>Easy web-based data entry.</li>
					<li>Download entire datasets in two clicks.</li>
					<li>Map georeferenced records in two clicks.</li>
					<li>Upload high-resolution images &amp; create species profile pages.</li>
					<li>Design custom species lists for any locality at multiple scales.</li>
					<li>Develop educational games with data.</li>
					<li>Create taxonomic keys.</li>
				</ol>
            </div>
			<div style="margin-top:10px;padding: 0px 10px;">
                The key organizational feature is that each museum or project is listed as a separate collection, so that one database group
                does not interfere with another. End users can select all "collections", or just a subset. This website is the central data
                portal for SCAN; all other project information can be found on the <a href="http://www.lep-net.org/" target="_blank">LepNet WordPress site</a>, including How-To-Guides and network
                updates.
            </div>
			<div style="margin-top:10px;padding: 0px 10px;">
                SCAN currently serves over 18 million records for 238,177 species, and 2,276,630 specimen/label images (7/16/18).
            </div>
		<div style="margin-top:10px;padding: 0px 10px;">
        <a href="mailto:neilscobb@gmail.com"><img src="<?php echo $clientRoot; ?>/images/layout/Help_Sessions.png" style="width:250px;" alt="Help sessions" /></a>
    </div>
		</div>

	<?php
	include($serverRoot."/footer.php");
	?>

</body>
</html>
