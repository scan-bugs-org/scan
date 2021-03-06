<?php
//error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($SERVER_ROOT.'/config/dbconnection.php');
 $SQL = "select CollectionName, rights from omcollections where rights != 'NULL' order by CollectionName;";
 header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Data Usage Guidelines</title>
		<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Guidelines for Acceptable Use of Data</h1>

			<h2>Recommended Citation Formats</h2>
			<div style="margin:10px">
				Use one of the following formats to cite data retrieved from the <?php echo $defaultTitle; ?> network:
				<div style="font-weight:bold;margin-top:10px;">
					General Citation:
				</div>
				<div style="margin:10px;">
					<?php 
					echo $defaultTitle.'. '.date('Y').'. '; 
					echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php. '; 
					echo 'Accessed on '.date('F d').'. '; 
					?>
				</div>
				
				<div style="font-weight:bold;margin-top:10px;">
					Usage of occurrence data from specific institutions:
				</div>
				<div style="margin:10px;">
					Biodiversity occurrence data published by: &lt;List of Collections&gt; 
					(Accessed through <?php echo $defaultTitle; ?> Data Portal, 
					<?php echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php'; ?>, YYYY-MM-DD)<br/><br/>
					<b>For example:</b><br/>
					Biodiversity occurrence data published by: 
					Field Museum of Natural History, Museum of Vertebrate Zoology, and New York Botanical Garden 
					(Accessed through <?php echo $defaultTitle; ?> Data Portal, 
					<?php echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php, '.date('Y-m-d').')'; ?>
				</div>
			</div>
			<div>
			</div>

			<a name="occurrences"></a>
			<h2>Occurrence Record Use Policy</h2>
		    <div>
				<ul>
					<li>
						While <?php echo $defaultTitle; ?> will make every effort possible to control and document the quality 
						of the data it publishes, the data are made available "as is". Any report of errors in the data should be 
						directed to the appropriate curators and/or collections managers. 
					</li>
					<li>
						<?php echo $defaultTitle; ?> cannot assume responsibility for damages resulting from mis-use or 
						mis-interpretation of datasets or from errors or omissions that may exist in the data. 
					</li>
					<li>
						It is considered a matter of professional ethics to cite and acknowledge the work of other scientists that 
						has resulted in data used in subsequent research. 
					</li>
                    <li>
                        <?php echo $defaultTitle; ?> expects that any use of data from this server will be accompanied with the 
                        appropriate citations and acknowledgments.
                    </li>
                    <li>
                        <?php echo $defaultTitle; ?> encourages users to contact the original investigator responsible for the 
                        data that they are accessing.  Where appropriate, researchers whose projects are integrally dependent 
                        on particular group of specimen data are encouraged to consider collaboration and/or co-authorship 
                        with original investigators.
                    </li>
					<li>
						<?php echo $defaultTitle; ?> asks that users not redistribute data obtained from this site without 
                        permission for data owners. However, links or references to this site may be freely posted.
					</li>
				</ul>
		    </div>

			<a name="collectionrights"></a>
            <h2>Collection Use Rights</h2>
            <p>
                Collection use rights are defined by the individual collection managers. Plese refer to the collection manager 
                if you have any questions.
            </p> 

            <div style="margin:15px;">
                <table width="100%" border="1">
                    <caption>Collection Rights</caption>
                    <tr>
                        <th width="52%" scope="col">Collection Name</th>
                        <th width="48%" scope="col">Rights</th>
                    </tr>
                    <?php
                        $conn = MySQLiConnectionFactory::getCon("readonly");
                        
                        if ($conn !== null) {
                            $results = $conn->query($SQL);
                            
                            while ($row = $results->fetch_object()) {
                                printf(
                                    '
                                        <tr>
                                            <td>%1$s</td>
                                            <td><a href="%2$s">%2$s</a></td>
                                        </tr>
                                    ', 
                                    $row->CollectionName, 
                                    $row->rights
                                );
                            }

                            $conn->close();                     
                        }
                    ?>
                </table>
            </div>
		
			<a name="images"></a>
			<h2>Images</h2>
		    <div style="margin:15px;">
                Images within this website have been generously contributed by their owners to promote education and research. 
                These contributors retain the full copyright for their images. Unless stated otherwise, images are made 
                available under the "Fair Use" provision of the U.S. Copyright Law 
                (<a href="http://www.copyright.gov/">http://www.copyright.gov/</a>). They may be 
                used only for personal or educational use and are NOT available for commercial use unless permission is first 
                obtained from the copyright holder. If any image is used in a non-commercial publication, report, or as a web link, 
                one must credit the photographer as well as the name of the website hosting the image. If you have any doubt or 
                questions regarding the use of an image, contact the author or the site manager.
		    </div>

			<h2>Notes on Specimen Records and Images</h2> 
		    <div style="margin:15px;">
				Specimens are used for scientific research and because of skilled preparation and 
				careful use they may last for hundreds of years. Some collections have specimens 
				that were collected over 100 years ago that are no longer occur within the area. 
				By making these specimens available on the web as images, their availability and 
				value improves without an increase in inadvertent damage caused by use. Note that 
				if you are considering making specimens, remember collecting normally requires 
				permission of the landowner and, in the case of rare and endangered plants, 
				additional permits may be required. It is best to coordinate such efforts with a 
				regional institution that manages a publically accessible collection.
			</div> 
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
