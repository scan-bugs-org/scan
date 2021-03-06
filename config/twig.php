<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$cacheDir = "/var/tmp/php/.twig-cache";
$loader = new FilesystemLoader(__DIR__ . "/../views");
$options = [
  "strict_variables" => true,
  "debug" => false,
  "cache" => $cacheDir,
];

if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0700, true);
}
$twig = new Environment($loader, $options);
