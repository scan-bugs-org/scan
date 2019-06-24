<?php

include_once($GLOBALS['SERVER_ROOT'] . '/config/dbconnection.php');

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

  private $zipFile;
  private $createdImgPaths;
  private $tmpZipPath;
  private $logFile;
  private $conn;
  private $collId;
  private $imgPathPrefix;
  private $imgUrlPrefix;

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
    $this->conn = MySQLiConnectionFactory::getCon("write");

    $this->collId = $collId;
    $this->zipFile = null;
    $this->createdImgPaths = array();
    $this->tmpZipPath = '';
    $this->logFile = $GLOBALS['LOG_PATH'] . '/' . 'batchupload.log';
    $this->resetLog();

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
  }

  public function load($postFile) {
    // Upload the archive
    if ($this->loadZip($postFile)) {
      $fileName = basename($postFile['name']);
      $this->logMsg("info", "Extracting $fileName...");
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
      $success = true;

      $zipMemberName = $this->zipFile->getNameIndex($i);
      $this->logMsg('info', "Found " . basename($zipMemberName));

      $this->logMsg('info', "Extracting " . basename($zipMemberName) . "...");
      $this->zipFile->extractTo($GLOBALS['TEMP_DIR_ROOT'], $zipMemberName);
      $tmpImgPath = $GLOBALS['TEMP_DIR_ROOT'] . '/' . $zipMemberName;

      if ($this->validateImageFile($tmpImgPath)) {
        $catalogNumber = explode('_', basename($tmpImgPath))[0];
        $this->logMsg('info', "Catalog number is $catalogNumber");

        $assocOccId = $this->getOccurrenceForCatalogNumber($catalogNumber);
        $this->processImage($tmpImgPath, $assocOccId);

      } else {
        $this->logMsg('warn', 'File is invalid, skipping...');
      }

      unlink($tmpImgPath);
      $this->logBreak();
    }
  }

  /**
   * Create thumbnail, large image and assoicate with a record
   * @param  string  $imgPath    Path to the source image
   * @param  integer $assocOccId An assocated occurrence, or -1 if none exists
   * @return bool                Whether the operation was completed successfully
   */
  private function processImage($imgPath, $assocOccId=-1) {
    $success = true;

    if (!file_exists($this->imgPathPrefix) && !mkdir($this->imgPathPrefix, 0777, true)) {
			$this->logMsg('error', "Unable to create directory: $this->imgPathPrefix");
			$success = false;
    }

    if ($success) {
      // Set target paths
      $tnFile = pathinfo($imgPath, PATHINFO_FILENAME) . '_tn.jpg';

      $imgTargetPath = $this->imgPathPrefix . basename($imgPath);
      $imgTnPath = $this->imgPathPrefix . $tnFile;
      $imgTnUrl = $this->imgUrlPrefix . $tnFile;

      // Create the thumbnail
      if (!file_exists($imgTnPath)) {
        if ($this->createImage($imgPath, $imgTnPath, $this->tnPixWidth)) {
          $proto = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http://' : 'https://';
          $this->logMsg('info', "Created thumbnail at $proto" . $_SERVER['SERVER_NAME'] . $imgTnUrl);
        } else {
          $this->logMsg('error', "Failed to create thumbnail at $imgTnUrl");
          $success = false;
        }
      } else {
        $this->logMsg('warn', "$imgTnUrl exists, refusing to overwrite");
      }
    }

    // Create the large image
    if ($success) {
      // list($sourceWidth, $sourceHeight, $sourceType, $sourceAttr) = getimagesize($sourcePath);
      // if ($sourceWidth > )
    }

    // Create a skeleton record
    if ($success && $assocOccId === -1) {
      $this->logMsg('info', "Linking to skeleton record...");

    }
    // Upload to existing record
    else if ($success) {
      $this->logMsg('info', "Linking image to associated occurrence...");
    }

    return $success;
  }

  private function createImage($sourcePath, $targetPath, $targetWidth) {
    $success = true;
    list($sourceWidth, $sourceHeight, $sourceType, $sourceAttr) = getimagesize($sourcePath);

    // Use ImageMagick to resize images
    if(array_key_exists('USE_IMAGE_MAGICK', $GLOBALS) && $GLOBALS['USE_IMAGE_MAGICK'] === 1) {
      $cmdStdout = false;
      if($targetWidth < 300){
        $cmdStdout = system("convert $sourcePath -thumbnail $targetWidth" . 'x' . ($targetWidth * 1.5) . " $targetPath");
      }
      else{
        $cmdStdout = system("convert $sourcePath -resize $targetWidth" . 'x' . ($targetWidth * 1.5) . " -quality $this->jpegCompression $targetPath");
      }

      if (!$cmdStdout) {
        $success = false;
        $this->logMsg("Error processing image with ImageMagick");
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
        imagecopyresized($tmpImg, $sourceGdImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        $success = imagejpeg($tmpImg, $targetPath, $this->jpgCompression);
        imagedestroy($tmpImg);
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

    // Check for underscore
    if ($success && strpos($fileBaseName, '_') === false) {
      $success = false;
      $this->logMsg('warn', "$fileBaseName's name should contain an underscore");
    }

    return $success;
  }

  /**
   * Checks the extension, mimeType, and naming scheme of the given zip file
   * @param  string $filePath Path to the file to validate
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

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = $finfo->file($filePath);
    $finfo->close();

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
        $msg = "Found associated occurrence: " . $row['sciname'];
        $this->logMsg("info", $msg);
      }

      $res->close();
    }

    if ($result === -1) {
      $this->logMsg("info", "No associated occurrences found");
    }

    return $result;
  }
}
?>
