<?php
/**
 * TamalBank API - Entry Point
 * Refactored with Clean Architecture
 */

require_once __DIR__ . '/bootstrap.php';

$app = new Application();
$app->run();