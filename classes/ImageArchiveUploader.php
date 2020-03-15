<?php

include_once($GLOBALS['SERVER_ROOT'] . '/config/dbconnection.php');
include_once($GLOBALS['SERVER_ROOT'] . '/classes/UuidFactory.php');

/**
 * Utility for validating and uploading image zip files
 */
class ImageArchiveUploader {

  const ZIP_MIME_TYPES = array(
    'application/zip',
    'application/x-zip-compressed',
    'multipart/x-zip',
    'application/x-compressed'
  );

  const IMG_MIME_TYPES = array(
    'image/bmp',
    'image/gif',
    'image/jpeg',
    'image/png',
    'image/tiff'
  );

  const IMG_EXTS= array(
    'bmp',
    'gif',
    'jpeg',
    'jpg',
    'png',
    'tiff',
    'tif'
  );

  const SQL_IMG_INSERT = "INSERT INTO images(occid, url, thumbnailurl, originalurl, owner) VALUES ";
  const SQL_SKEL_INSERT = "INSERT INTO omoccurrences(collid, catalognumber) VALUES ";

  private $zipFile;
  private $createdImgPaths;
  private $tmpZipPath;
  private $logFile;
  private $conn;
  private $collId;
  private $imgPathPrefix;
  private $imgUrlPrefix;
  private $catalogRegexes;

  private $tnPixWidth = 200;
  private $tnPixHeight = 70;
  private $webPixWidth = 1600;
  private $lgPixWidth = 3168;
  private $webFileSizeLimit = 300000;
  private $jpgCompression= 90;

  /**
   * Creates a new archive uploader
   * @param _FILE[member] $postFile The zip archive POST file
   */
  public function __construct($collId){
    register_shutdown_function(array($this, '__destruct'));
	  
    $this->conn = MySQLiConnectionFactory::getCon("write");

    $this->collId = $collId;
    $this->zipFile = null;
    $this->createdImgPaths = array();
    $this->tmpZipPath = '';
    $this->logFile = $GLOBALS['LOG_PATH'] . '/' . 'batchupload-' . UuidFactory::getUuidV4() .'.log';
    $this->resetLog();

    // Retrieve catalogRegex
    $this->catalogRegexes = $this->getCatalogRegex($collId);

    if (count($this->catalogRegexes) == 0) {
      $this->logMsg('error', "This collection doesn't support catalog number recognition");
      $this->logMsg('error', "Contact your collection administrator");
      $this->logBreak();
    }

    // Set image paths
    $this->imgPathPrefix = $GLOBALS['IMAGE_ROOT_PATH'];
    $this->imgUrlPrefix = $GLOBALS['IMAGE_ROOT_URL'];

    if (substr($this->imgPathPrefix, -1) !== '/') {
      $this->imgPathPrefix .= '/';
    }

    if (substr($this->imgUrlPrefix, -1) !== '/') {
      $this->imgUrlPrefix .= '/';
    }

    $imgSubPath = 'misc/'.date('Ym').'/';
    $this->imgPathPrefix .= $imgSubPath;
    $this->imgUrlPrefix .= $imgSubPath;

    // Set image size (in px and in filesize)
    if(array_key_exists('imgTnWidth', $GLOBALS)){
      $this->tnPixWidth = $GLOBALS['imgTnWidth'];
    }
    if(array_key_exists('imgWebWidth', $GLOBALS)){
      $this->webPixWidth = $GLOBALS['imgWebWidth'];
    }
    if(array_key_exists('imgLgWidth', $GLOBALS)){
      $this->lgPixWidth = $GLOBALS['imgLgWidth'];
    }
    if(array_key_exists('imgFileSizeLimit', $GLOBALS)){
      $this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
    }

    ini_set('memory_limit', '512M');
  }

  /**
   * Cleans up temporary files
   */
  public function __destruct() {
    if (!$this->conn === null) {
      $this->conn->close();
      $this->conn = null;
    }

    if ($this->zipFile !== null) {
      $this->zipFile->close();
    }

    if ($this->tmpZipPath !== '') {
      unlink($this->tmpZipPath);
    }

    if (file_exists($this->logFile)) {
      unlink($this->logFile);
    }
  }

  public function load($postFile) {
    // Upload the archive
    if ($this->loadZip($postFile)) {
      $fileName = basename($postFile['name']);
      $this->logMsg("info", "Starting image processing on $fileName...");
      $this->logBreak();

      // Unzip / process the images
      $this->loadImages();

    } else {
      $this->logMsg("error", "Invalid Zip Archive");
    }
  }

  /**
   * Logs a messsage to batchupload.log in $GLOBALS['LOG_PATH']
   * @param  string $level Log level to display in log
   * @param  string $msg   Message to log
   */
  public function logMsg($level, $msg) {
    $fh = fopen($this->logFile, 'a');
    fwrite(
      $fh,
      date("[Y-m-d h:i:sa]") . '[' . strtoupper($level) . '] ' . $msg . "\n"
    );
    fclose($fh);
  }

  /**
   * Logs a blank line to batchupload.log in $GLOBALS['LOG_PATH']
   */
  public function logBreak() {
    $fh = fopen($this->logFile, 'a');
    fwrite($fh, "\n");
    fclose($fh);
  }

  /**
   * @return string The content of batchupload.log in $GLOBALS['LOG_PATH']
   */
  public function getLogContent() {
    $contents = '';
    $fileSize = filesize($this->logFile);

    if ($fileSize > 0) {
      $fh = fopen($this->logFile, 'r');
      $contents = fread($fh, $fileSize);
      fclose($fh);
    }

    return $contents;
  }

  /**
   * Deletes and re-creates the log file
   */
  public function resetLog() {
    // Delete the current log if it exists
    if (file_exists($this->logFile)) {
      unlink($this->logFile);
    }

    // Create a new log file
    fclose(fopen($this->logFile, 'w'));
  }

  /**
   * @param $collid Collection ID to retrieve the catalog number pattern for
   * @return Array of strings representing the regexes for the collid catalog numbers
   */
  function getCatalogRegex($collid) {
    $sql = "select speckeypattern from specprocessorprojects where collid = $collid;";
    $results = [];
    if ($res = $this->conn->query($sql)) {
      while($row = $res->fetch_assoc()) {
        if ($row !== null) {
          $pattern = $row['speckeypattern'];
          if ($pattern !== null) {
            array_push($results, $pattern);
          }
        }
      }
      $res->close();
    }

    return $results;
  }

  /**
   * Validates and loads the given POST zip file into basename($postFile['name'])
   * in $GLOBALS['TEMP_DIR_ROOT']
   * @param  _FILE[member] $postFile The POSTed zip file
   * @return bool Whether the validation/load was successful
   */
  private function loadZip($postFile) {
    $this->tmpZipPath = $GLOBALS['TEMP_DIR_ROOT'] . '/' . basename($postFile['name']);
    move_uploaded_file($postFile['tmp_name'], $this->tmpZipPath);

    if ($this->validateZipFile($this->tmpZipPath)) {
      $this->zipFile = new ZipArchive();
      if ($this->zipFile->open($this->tmpZipPath, ZipArchive::CHECKCONS) !== ZipArchive::ER_NOZIP) {
        $this->logMsg('info', "Upload for " . basename($this->tmpZipPath) . " succeeded");
        return true;
      }
    }

    $this->logMsg('error', "Upload failed for " . basename($this->tmpZipPath));
    $this->zipFile = null;
    return false;
  }

  /**
   * Validates/extracts the images in $this->zipFile to
   * $GLOBALS['TEMP_DIR_ROOT']
   */
  private function loadImages() {
    for ($i = 0; $i < $this->zipFile->numFiles; $i++) {

      $zipMemberName = $this->zipFile->getNameIndex($i);

      $this->logMsg('info', "Found " . basename($zipMemberName));

      $matchedCatalogNumber = false;
      $catalogNumberMatches = [];
      for ($j = 0; $j < count($this->catalogRegexes); $j++) {
        if (preg_match($this->catalogRegexes[$j], $zipMemberName, $catalogNumberMatches)) {
          $matchedCatalogNumber = true;
          break;
        }
      }

      if (!$matchedCatalogNumber) {
        $this->logMsg('info', "Catalog number not found in $zipMemberName. Skipping...");
        $this->logBreak();
        continue;
      }

      // The way regex is set up in the db, preg_match will first match the whole file,
      // then match the catalog number, then match anything following the catalog number; We just want the
      // catalog number
      $catalogNumber = $catalogNumberMatches[1];
      $this->logMsg('info', "Catalog number is $catalogNumber");

      $this->logMsg('info', "Extracting " . basename($zipMemberName) . "...");
      $this->zipFile->extractTo($GLOBALS['TEMP_DIR_ROOT'], $zipMemberName);
      $tmpImgPath = $GLOBALS['TEMP_DIR_ROOT'] . '/' . $zipMemberName;

      if (is_dir($tmpImgPath)) {
        $this->logMsg('info', "$tmpImgPath is a directory. Skipping...");
        $this->logBreak();
        continue;
      }

      if ($this->validateImageFile($tmpImgPath)) {

        // Look up associated record
        $assocOccId = $this->getOccurrenceForCatalogNumber($catalogNumber);

        // Or create a skeleton record
        if ($assocOccId === -1) {
           $this->logMsg('info', "Creating to skeleton record...");
           $sql = ImageArchiveUploader::SQL_SKEL_INSERT . "($this->collId, '$catalogNumber');";
           if ($this->conn->query($sql) === TRUE) {
             $assocOccId = $this->getOccurrenceForCatalogNumber($catalogNumber);
             $this->logMsg('info', "Created skeleton record with occid $assocOccId and catalogNumber $catalogNumber");
           } else {
             $this->logMsg('warn', "Failed creating skeleton record for catalog number $catalogNumber: " . $this->conn->error);
           }
        }

        // Upload image to record
        $this->logMsg('info', "Linking image to occurrence...");
        $this->processImage($tmpImgPath, $assocOccId);

      } else {
        $this->logMsg('warn', 'File is invalid, skipping...');
      }

      if (file_exists($tmpImgPath)) {
        unlink($tmpImgPath);
      }
      $this->logBreak();
    }
  }

  /**
   * Create thumbnail, large image and assoicate with a record
   * @param  string  $imgPath    Path to the source image
   * @param  integer $assocOccId An assocated occurrence, or -1 if none exists
   * @return bool                Whether the operation was completed successfully
   */
  private function processImage($imgPath, $assocOccId) {
    $success = true;

    if (!file_exists($this->imgPathPrefix) && !mkdir($this->imgPathPrefix, 0777, true)) {
			$this->logMsg('error', "Unable to create directory: $this->imgPathPrefix");
			$success = false;
    }

    if ($success) {
      // Set target paths
      $imgTargetPath = $this->imgPathPrefix . basename($imgPath);
      $imgTargetUrl = $this->imgUrlPrefix . basename($imgPath);

      // If this image has already been uploaded, quit right now
      $existingOccImgs = $this->getImageUrlsForOccid($assocOccId);
      if (in_array($imgTargetUrl, $existingOccImgs)) {
        $success = false;
        $this->logMsg('warn', 'Image at url ' . $_SERVER['SERVER_NAME'] . $imgTargetUrl . "is already linked to occurrence $assocOccId. Skipping...");
      } else {
        // Copy image to permanent spot
        if (file_exists($imgTargetPath)) {
          $this->logMsg('warn', "$imgTargetUrl already exists, refusing to overwrite");
          $imgPath = $imgTargetPath;
        } else if (rename($imgPath, $imgTargetPath)) {
          $this->logMsg('info', "Image imported successfully");
          $imgPath = $imgTargetPath;
        } else {
          $this->logMsg('error', "Failed to import image");
          $success = false;
        }
      }
    }

    // Create the thumbnail
    if ($success) {
      // Set target paths
      $tnFile = pathinfo($imgPath, PATHINFO_FILENAME) . '_tn.jpg';
      $lgFile = pathinfo($imgPath, PATHINFO_FILENAME) . '_lg.jpg';

      $imgTnPath = $this->imgPathPrefix . $tnFile;
      $imgTnUrl = $this->imgUrlPrefix . $tnFile;

      $imgLgPath = $this->imgPathPrefix . $lgFile;
      $imgLgUrl = $this->imgUrlPrefix . $lgFile;

      if (!file_exists($imgTnPath)) {
        if ($this->createImage($imgPath, $imgTnPath, $this->tnPixWidth)) {
          $proto = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http://' : 'https://';
          $this->logMsg('info', "Thumbnail created successfully");
        } else {
          $this->logMsg('error', "Failed to create thumbnail");
          $success = false;
        }
      } else {
        $this->logMsg('warn', "$imgTnUrl exists, refusing to overwrite");
      }
    }

    // Create the large image
    if ($success) {
      list($sourceWidth, $sourceHeight, $sourceType, $sourceAttr) = getimagesize($imgPath);
      $sourceFileSize = filesize($imgPath);

      // If source image is sufficiently to wide or too big to serve as a web image,
      // scale it down
      if ($sourceWidth > $this->lgPixWidth * 1.2 || $sourceFileSize > $this->webFileSizeLimit) {
        if ($this->createImage($imgPath, $imgLgPath, $this->lgPixWidth)) {
          $this->logMsg('info', "Large image created successfully");
        } else {
          $this->logMsg('error', "Failed to create large image");
          $success = false;
        }
      }
      // Otherwise, just use the original as the large image
      else {
        $this->logMsg('info', "Using original as large image");
        $imgLgPath = $imgTargetPath;
        $imgLgUrl = $imgTargetUrl;
      }
    }

    // Insert into db
    if ($success) {
      $owner = array_key_exists('USERNAME', $GLOBALS) ? $GLOBALS['USERNAME'] : '';

      // INSERT INTO images(occid, url, thumbnailurl, originalurl, owner) VALUES;
      $sql = ImageArchiveUploader::SQL_IMG_INSERT . "($assocOccId, '$imgLgUrl', '$imgTnUrl', '$imgTargetUrl', '$owner')";
      if ($this->conn->query($sql) === TRUE) {
        $this->logMsg('info', "Successfully uploaded image for occurrence $assocOccId: " . $_SERVER['SERVER_NAME'] . $imgTargetUrl);
      } else {
        $success = false;
        $this->logMsg('warn', "Failed to link image to occurrence $assocOccId: " . $this->conn->error);
      }
    }

    return $success;
  }

  private function createImage($sourcePath, $targetPath, $targetWidth) {
    $success = true;
    list($sourceWidth, $sourceHeight, $sourceType, $sourceAttr) = getimagesize($sourcePath);

    // Use ImageMagick to resize images
    if(array_key_exists('USE_IMAGE_MAGICK', $GLOBALS) && $GLOBALS['USE_IMAGE_MAGICK'] === 1) {
      $cmdStdout = false;
      $cmdRetCode = -1;
      if($targetWidth < 300){
	      $imgCmd = "convert $sourcePath -thumbnail $targetWidth" . 'x' . ($targetWidth * 1.5) . " $targetPath 2>&1";
      }
      else{
        $imgCmd = "convert $sourcePath -resize $targetWidth" . 'x' . ($targetWidth * 1.5) . " -quality $this->jpegCompression $targetPath 2>&1";
      }

      $cmdStdout = system($imgCmd, $cmdRetCode);

      if ($cmdRetCode !== 0) {
        $success = false;
        $this->logMsg('error', "Error processing image with ImageMagick: $cmdStdout");
      }
    }
    // GD is installed and working
    else if(extension_loaded('gd') && function_exists('gd_info')) {

      $targetHeight = round($sourceHeight * ($targetWidth / $sourceWidth));
      if ($targetWidth > $sourceWidth) {
        $targetWidth = $sourceWidth;
        $targetHeight = $sourceHeight;
      }

      $sourceGdImg = '';
      $format = '';

      if($sourceType === 'gif'){
        $sourceGdImg = imagecreatefromgif($sourcePath);
        $format = 'image/gif';
      }
      else if($sourceType === 'png'){
        $sourceGdImg = imagecreatefrompng($sourcePath);
        $format = 'image/png';
      }
      else {
        //JPG assumed
        $sourceGdImg = imagecreatefromjpeg($sourcePath);
        $format = 'image/jpeg';
      }

      if ($sourceGdImg === '') {
        $success = false;
        $this->logMsg('error', 'Error processing image with GD');
      }

      if ($success) {
        $tmpImg = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tmpImg) {
          imagecopyresized($tmpImg, $sourceGdImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
          $success = imagejpeg($tmpImg, $targetPath, $this->jpgCompression);
          imagedestroy($tmpImg);
        } else {
          $this->logMsg('error', 'Error processing image with GD');
          $success = false;
        }
      }
    }
    // Neither ImageMagick nor GD are installed
    else{
      $this->logMsg('error', 'No appropriate image handler for image conversions');
      $success = false;
    }

    if($success && !file_exists($targetPath)){
      $this->logMsg("info", "An error occurred creating $targetPath");
      $success = false;
    }

    return $success;
  }

  /**
   * Checks the extension, mimeType, and naming scheme of the given image file
   * @param  string $filePath Path to the file to validate
   * @return bool             Whether all checks passed
   */
  private function validateImageFile($filePath) {
    $success = true;
    $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $fileBaseName = basename($filePath);

    // First, check file ext
    if (!in_array($fileExt, ImageArchiveUploader::IMG_EXTS)) {
      $success = false;
      $this->logMsg('warn', "Invalid extension for $fileBaseName");
    }

    // Then mime type
    if ($success) {
      $mimeType = ImageArchiveUploader::getMimeType($filePath);
      if (!in_array($mimeType, ImageArchiveUploader::IMG_MIME_TYPES)) {
        $success = false;
        $this->logMsg('warn', "$fileBaseName is not an image file or may be corrupted");
      }
    }

    return $success;
  }

  /**
   * Checks the extension, mimeType, and naming scheme of the given zip file
   * @param  string $filePath Path to the fileF to validate
   * @return bool             Whether all checks passed
   */
  private function validateZipFile($filePath) {
    $success = true;
    $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $fileBaseName = basename($filePath);

    // Extension
    if ($fileExt !== 'zip') {
      $success = false;
      $this->logMsg('error', "Invalid extension for $fileBaseName");
    }

    // Then mime type
    if ($success) {
      $mimeType = ImageArchiveUploader::getMimeType($filePath);
      if (!in_array($mimeType, ImageArchiveUploader::ZIP_MIME_TYPES)) {
        $success = false;
        $this->logMsg('error', "$fileBaseName is not a zip file or may be corrupted");
      }
    }

    return $success;
  }

  /**
   * @param  string $filePath Path to file to evaluate
   * @return string MIME type for the given $filePath
   */
  private static function getMimeType($filePath) {
    if (function_exists('mime_content_type')) {
      return strtolower(mime_content_type($filePath));
    }

    $finfo_obj = finfo_open(FILEINFO_MIME_TYPE);
    $type = finfo_file($finfo_obj, $filePath);
    finfo_close($finfo_obj);

    return strtolower($type);
  }

  /**
   * @param  string $catalogNumber Occurrence catalog number
   * @return int occid for the catalogNumber
   */
  private function getOccurrenceForCatalogNumber($catalogNumber) {
    $sql = "SELECT occid, sciname FROM omoccurrences " .
      "WHERE collid = $this->collId " .
      "AND catalognumber = '$catalogNumber' LIMIT 1";
    $result = -1;
    if ($res = $this->conn->query($sql)) {
      $row = $res->fetch_assoc();

      if ($row !== null) {
        $result = $row['occid'];
        if ($row['sciname'] != '') {
          $msg = "Found associated occurrence: " . $row['sciname'];
          $this->logMsg("info", $msg);
        }
      }

      $res->close();
    }

    return $result;
  }

  /**
   * @param  string $catalogNumber Occurrence catalog number
   * @return int occid for the catalogNumber
   */
  private function getImageUrlsForOccid($occId) {
    $sql = "SELECT url FROM images " .
      "WHERE occid = $occId";
    $result = array();
    if ($res = $this->conn->query($sql)) {
      while($row = $res->fetch_assoc()) {
        if ($row !== null && $row['url'] !== null) {
          array_push($result, $row['url']);
        }
      }

      $res->close();
    }

    return $result;
  }
}
?>
