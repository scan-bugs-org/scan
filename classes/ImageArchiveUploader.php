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
    // Unzip the archive
    if ($this->loadZip($postFile)) {
      $fileName = basename($postFile['name']);
      $this->logMsg("info", "Extracting $fileName...");

      // Images here?

      $this->logBreak();
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

        // Found associated occurence
        if ($assocOccId !== -1) {
          $this->logMsg('info', "Uploading image to associated occurrence...");
        }
        // Create skeleton record
        else {
          $this->logMsg('info', "Creating skeleton record...");
        }

      } else {
        $this->logMsg('warn', 'File is invalid, skipping...');
      }

      unlink($tmpImgPath);
      $this->logBreak();
    }
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
