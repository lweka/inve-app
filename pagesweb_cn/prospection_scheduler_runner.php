<?php
define('PROSPECTION_RUNNER_MODE', true);
if (PHP_SAPI === 'cli') {
    if (!isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] === '') {
        $_SERVER['SERVER_NAME'] = 'localhost';
    }
    if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    }
}
require_once __DIR__ . '/prospection_manager.php';
