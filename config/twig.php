<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . "/../views");
$options = [
  "strict_variables" => true,
  "debug" => false,
  "cache" => false
];

$twig = new Environment($loader, $options);
