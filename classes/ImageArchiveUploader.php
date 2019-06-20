<?php

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

  private $collectionPermissionSql;
  private $zipFile;
  private $tmpImgPaths;
  private $tmpZipPath;
  private $logFile;

  public function __construct($postFile){
    $this->zipFile = new ZipArchive();
    $this->tmpImgPaths = array();
    $this->tmpZipPath = '';
    $this->logFile = $GLOBALS['LOG_PATH'] . '/' . 'batchupload.log';

    if (file_exists($this->logFile)) {
      unlink($this->logFile);
    }

    $this->collectionPermissionSql = <<< EOD
select distinct c.collid, c.collectionname
from omcollections c
inner join userroles r on c.collid = r.tablepk
inner join users u on r.uid = u.uid
where (r.role = 'CollEditor' or r.role = 'CollAdmin') and u.uid =
EOD;
    $this->collectionPermissionSql .= $GLOBALS["SYMB_UID"];
    $this->collectionPermissionSql .= " order by c.collectionname";

    // Unzip the archive
    if ($this->loadZip($postFile)) {
      $fileName = basename($postFile['name']);
      $this->logMsg("info", "$fileName uploaded successfully, extracting...");
      $this->loadImages();
    } else {
      $this->logMsg("error", "Invalid Zip Archive");
    }
  }

  public function __destruct() {
    $this->zipFile->close();
    foreach ($this->tmpImgPaths as $path) {
      unlink($path);
    }
    if ($this->tmpZipLocation !== '') {
      unlink($this->tmpZipLocation);
    }
  }

  public function logMsg($level, $msg) {
    $fh = fopen($this->logFile, 'a');
    fwrite(
      $fh,
      date("[Y-m-d h:i:sa]") . '[' . strtoupper($level) . '] ' . $msg . "\n"
    );
    fclose($fh);
  }

  public function getLogContent() {
    $fh = fopen($this->logFile, 'r');
    $contents = fread($fh, filesize($this->logFile));
    fclose($fh);
    return $contents;
  }

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

  private function loadImages() {
    for ($i = 0; $i < $this->zipFile->numFiles; $i++) {
      $success = true;
      $zipMemberName = $this->zipFile->getNameIndex($i);
      $zipMemberExt = strtolower(pathinfo($zipMemberName, PATHINFO_EXTENSION));

      if (in_array($zipMemberExt, ImageArchiveUploader::IMG_EXTS)) {
        $this->zipFile->extractTo($GLOBALS['TEMP_DIR_ROOT'], $zipMemberName);
        $tmpImgPath = $GLOBALS['TEMP_DIR_ROOT'] . '/' . $zipMemberName;
        $zipMemberType = ImageArchiveUploader::getMimeType($tmpImgPath);

        if (in_array($zipMemberType, ImageArchiveUploader::IMG_MIME_TYPES)) {
          array_push($this->tmpImgPaths, $tmpImgPath);
        } else {
          unlink($tmpImgPath);
          $success = false;
        }
      } else {
        $success = false;
      }

      if ($success) {
        $this->logMsg('info', "Found " . basename($zipMemberName));
      } else {
        $this->logMsg('error', basename($zipMemberName) . " is not a valid image file");
      }
    }
  }

  private static function getMimeType($filePath) {
    if (function_exists('mime_content_type')) {
      return strtolower(mime_content_type($filePath));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = $finfo->file($filePath);
    $finfo->close();

    return strtolower($type);
  }
}
?>
