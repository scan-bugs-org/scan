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
  private $tmpImgPaths;
  private $tmpZipPath;
  private $logFile;
  private $conn;
  private $collId;

  /**
   * Creates a new archive uploader
   * @param _FILE[member] $postFile The zip archive POST file
   */
  public function __construct($collId, $postFile){
    $this->conn = MySQLiConnectionFactory::getCon("write");

    $this->collId = $collId;
    $this->zipFile = new ZipArchive();
    $this->tmpImgPaths = array();
    $this->tmpZipPath = '';
    $this->logFile = $GLOBALS['LOG_PATH'] . '/' . 'batchupload.log';

    if (file_exists($this->logFile)) {
      unlink($this->logFile);
    }

    ini_set('memory_limit', '512M');

    // Unzip the archive
    if ($this->loadZip($postFile)) {
      $fileName = basename($postFile['name']);
      $this->logMsg("info", "$fileName uploaded successfully, extracting...");
      $this->logBreak();

      $this->loadImages();

    } else {
      $this->logMsg("error", "Invalid Zip Archive");
    }
  }

  /**
   * Cleans up temporary files
   */
  public function __destruct() {
    if (!$this->conn === null) {
      $this->conn->close();
      $this->conn = null;
    }
    $this->zipFile->close();
    foreach ($this->tmpImgPaths as $path) {
      unlink($path);
    }
    if ($this->tmpZipLocation !== '') {
      unlink($this->tmpZipLocation);
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
    $fh = fopen($this->logFile, 'r');
    $contents = fread($fh, filesize($this->logFile));
    fclose($fh);
    return $contents;
  }

  /**
   * Validates and loads the given POST zip file into basename($postFile['name'])
   * in $GLOBALS['TEMP_DIR_ROOT']
   * @param  _FILE[member] $postFile The POSTed zip file
   * @return bool Whether the validation/load was successful
   */
  private function loadZip($postFile) {
    $fileExt = strtolower(pathinfo($postFile['name'], PATHINFO_EXTENSION));
    $mimeType = ImageArchiveUploader::getMimeType($postFile['tmp_name']);
    $localFile = '';

    if ($fileExt === 'zip' && in_array($mimeType, ImageArchiveUploader::ZIP_MIME_TYPES)) {
      $this->tmpZipLocation = $GLOBALS['TEMP_DIR_ROOT'] . '/' . basename($postFile['name']);
      move_uploaded_file($postFile['tmp_name'], $this->tmpZipLocation);
      if ($this->zipFile->open($this->tmpZipLocation, ZipArchive::CHECKCONS) !== ZipArchive::ER_NOZIP) {
        return true;
      }
    }
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
      $zipMemberExt = strtolower(pathinfo($zipMemberName, PATHINFO_EXTENSION));

      if (in_array($zipMemberExt, ImageArchiveUploader::IMG_EXTS)) {
        $this->zipFile->extractTo($GLOBALS['TEMP_DIR_ROOT'], $zipMemberName);
        $tmpImgPath = $GLOBALS['TEMP_DIR_ROOT'] . '/' . $zipMemberName;
        $zipMemberType = ImageArchiveUploader::getMimeType($tmpImgPath);

        // Process image
        if (in_array($zipMemberType, ImageArchiveUploader::IMG_MIME_TYPES)) {
          $this->logMsg('info', "Found " . basename($zipMemberName));
          array_push($this->tmpImgPaths, $tmpImgPath);

          $catalogNumber = explode('_', basename($tmpImgPath))[0];
          $this->logMsg('info', "Catalog number is $catalogNumber");

          // Found associated occurence
          $assocOccId = $this->getOccurrenceForCatalogNumber($catalogNumber);
          if ($assocOccId !== -1) {
            $this->logMsg('info', "Uploading image to associated occurrence ($assocOccId)...");
          }
          // Create skeleton record
          else {
            $this->logMsg('info', "Creating skeleton record...");
          }

        } else {
          unlink($tmpImgPath);
          $success = false;
        }
      } else {
        $success = false;
      }

      if (!$success) {
        $this->logMsg('error', basename($zipMemberName) . " is not a valid image file");
      }

      $this->logBreak();
    }
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
    $sql = "SELECT occid, sciname FROM omoccurrences WHERE collid = $this->collId AND catalognumber = '$catalogNumber' LIMIT 1";
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
