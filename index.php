<?php #declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

$menuValidator = new \App\MenuValidator();
$result = $menuValidator->validateMenu('https://backend-challenge-summer-2018.herokuapp.com/challenges.json?id=2');

echo $result;
