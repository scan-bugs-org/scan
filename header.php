<SCRIPT LANGUAGE=JAVASCRIPT>
<!--
if (top.frames.length!=0)
  top.location=self.document.location;
// -->
</SCRIPT>
<table id="maintable" cellspacing="0">
	<tr style="" >
		<td class="header" colspan="3">
			<div style="clear:both;width:100%;height:150px;border-bottom:1px solid #333333;background-color:#000000;">
				<div style="float:left;">
					<img style="" src="<?php echo $clientRoot; ?>/images/layout/scan_logo_color.jpg" />
				</div>
				<div style="float:right;">
					<img style="" src="<?php echo $clientRoot; ?>/images/layout/header.gif" />
				</div>
			</div>
			<div id="top_navbar">
				<div id="right_navbarlinks">
					<?php
					if($userDisplayName){
					?>
						<span style="">
							Welcome <?php echo $userDisplayName; ?>!
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a>
						</span>
					<?php
					}
					else{
					?>
						<span style="">
							<a href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
								Log In
							</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/newprofile.php">
								New Account
							</a>
						</span>
					<?php
					}
					?>
					<span style="margin-left:5px;margin-right:5px;">
						<a href='<?php echo $clientRoot; ?>/sitemap.php'>Sitemap</a>
					</span>

				</div>
				<ul id="hor_dropdown">
					<li>
						<a href="<?php echo $clientRoot; ?>/index.php" >Home</a>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/collections/index.php" >Search</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/index.php" >Search Collections</a>
							</li>
							<li>
                                <a href="<?php echo $clientRoot; ?>/spatial/index.php" target="_blank">Spatial Module</a>
                            </li>
							<li>
                                                            	<a href="<?php echo $clientRoot; ?>/collections/map/mapinterface.php" target="_blank">Map Search</a>
                                                        </li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist" >Dynamic Checklist</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/taxa/admin/taxonomydynamicdisplay.php" >Taxonomy Explorer</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/imagelib/search.php" >Images</a>
            <ul>
              <li><a href="<?php echo $clientRoot; ?>/imagelib/search.php" >Image Search</a></li>
              <li><a href="<?php echo $clientRoot; ?>/imagelib/imagebatch.php" >Batch Upload</a></li>
            </ul>
					</li>
					<li>
						<a href="#" >Fauna Projects</a>
						<ul>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=13" >Arthropods of Monahans Sandhills State Park, Texas</a>
							</li>
	      <li>
                                                                <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=108" >Camel Spiders of North America</a>
                                                        </li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=102" >Curculionoidea of Sonora, Mexico (&quot;Curcu-Sonora&quot;)</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=33" >Eleodes of Arizona</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=5" >Lepidoptera of Arizona</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=4" >National Park Service</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=95" >Neotropical Xyleborini</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=25" >Scarabaeoidea of Arizona</a>
							</li>
              <li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=1" >Weevils of North America</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/collections/misc/collstats.php" >Statistics</a>
					</li>
					<li>
						<a href="#" >Other Networks</a>
						<ul>
							<li>
								<a href="#" >Animals ></a>
								<ul>
                  <li>
										<a href="http://symbiota4.acis.ufl.edu/scan/lepnet/portal/index.php" target="_blank" >LepNet</a>
									</li>
                  <li>
										<a href="http://invertebase.org/portal/index.php" target="_blank" >InvertEBase</a>
									</li>
									<li>
										<a href="http://symbiota.org/neotrop/entomology/index.php" target="_blank" >Neotropical Entomology</a>
									</li>
									<li>
										<a href="http://madrean.org/symbfauna/projects/index.php" target="_blank" >Madrean Archipelago Biodiversity Assessment (MABA) - Fauna</a>
									</li>
								</ul>
							</li>
							<li>
								<a href="#" >Fungi & Lichens ></a>
								<ul>
									<li>
										<a href="http://mycoportal.org/portal/index.php" target="_blank" >MyCoPortal</a>
									</li>
									<li>
										<a href="http://lichenportal.org/portal/index.php" target="_blank" >Consortium of North American Lichen Herbaria</a>
									</li>
									<li>
										<a href="http://lichenportal.org/arctic/index.php" target="_blank" >Arctic Lichen Flora</a>
									</li>
								</ul>
							</li>
							<li>
								<a href="#" >Plants & Algae ></a>
								<ul>
									<li>
										<a href="http://swbiodiversity.org/seinet/index.php" target="_blank" >SEINet</a>
									</li>
									<li>
										<a href="http://sernecportal.org/portal/" target="_blank" >SouthEast Regional Network of Expertise and Collections (SERNEC)</a>
									</li>
									<li>
										<a href="http://midwestherbaria.org/portal/index.php" target="_blank" >Consortium of Midwest Herbaria</a>
									</li>
									<li>
										<a href="http://intermountainbiota.org/portal/index.php" target="_blank" >Intermountain Region Herbaria Network (IRHN)</a>
									</li>
									<li>
										<a href="http://nansh.org/portal/index.php" target="_blank" >North American Network of Small Herbaria</a>
									</li>
									<li>
										<a href="http://ngpherbaria.org/portal/index.php" target="_blank" >Northern Great Plains Herbaria</a>
									</li>
									<li>
										<a href="http://portal.neherbaria.org/portal/" target="_blank" >Consortium of Northeastern Herbaria (CNH)</a>
									</li>
									<li>
										<a href="http://swbiodiversity.unm.edu/" target="_blank" >New Mexico Biodiversity Portal</a>
									</li>
									<li>
										<a href="http://madrean.org/symbflora/projects/index.php?proj=74" target="_blank" >Madrean Archipelago Biodiversity Assessment (MABA) - Flora</a>
									</li>
									<li>
										<a href="http://herbariovaa.org/" target="_blank" >Herbario Virtual Austral Americano</a>
									</li>
									<li>
										<a href="http://cotram.org/" target="_blank" >CoTRAM – Cooperative Taxonomic Resource for Amer. Myrtaceae</a>
									</li>
									<li>
										<a href="http://symbiota.org/neotrop/plantae/index.php" target="_blank" >Neotropical Flora</a>
									</li>
									<li>
										<a href="http://www.pacificherbaria.org/" target="_blank" >Consortium of Pacific Herbaria</a>
									</li>
									<li>
										<a href="http://bryophyteportal.org/portal/" target="_blank" >Consortium of North American Bryophyte Herbaria</a>
									</li>
									<li>
										<a href="http://bryophyteportal.org/frullania/" target="_blank" >Frullania Collaborative Research Network</a>
									</li>
									<li>
										<a href="http://macroalgae.org/portal/index.php" target="_blank" >Macroalgal Consortium Herbarium Portal</a>
									</li>
								</ul>
							</li>
							<li>
								<a href="#" >Multi-Phyla ></a>
								<ul>
									<li>
										<a href="http://stricollections.org/portal/" target="_blank" >Smithsonian Tropical Research Institute Portal (STRI)</a>
									</li>
									<li>
										<a href="http://greatlakesinvasives.org/portal/index.php" target="_blank" >Aquatic Invasives</a>
									</li>
								</ul>
							</li>
							<li>
								<a href="http://collections.ala.org.au/" target="_blank" >Atlas of Living Australia</a>
							</li>
                            <li>
								<a href="http://www.gbif.org/" target="_blank" >GBIF</a>
							</li>
                            <li>
								<a href="https://www.idigbio.org/" target="_blank" >iDigBio</a>
							</li>
							<li>
								<a href="http://splink.cria.org.br/" target="_blank" >Species Link</a>
							</li>
                        </ul>
					</li>
					<li>
						<a href="#" >Symbiota</a>
						<ul>
							<li>
								<a href="http://symbiota.org/docs/" target="_blank" >Symbiota</a>
							</li>
							<li>
								<a href="http://symbiota.org/docs/symbiota-introduction/symbiota-help-pages/" target="_blank" >Symbiota Help</a>
							</li>
							<li>
								<a href="http://symbiota.org/docs/google-group/" target="_blank" >Symbiota Google Group</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" >Contact</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/misc/collprofiles.php" >Partners</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/misc/contacts.php" >Contacts</a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</td>
	</tr>
    <tr>
		<td class='middlecenter'  colspan="3">
