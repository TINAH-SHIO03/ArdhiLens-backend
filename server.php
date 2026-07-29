<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 */
$publicPath = getcwd();

if ($uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
)) {
    $requestedFile = $publicPath.$uri;

    if ($uri !== '/' && file_exists($requestedFile)) {
        return false;
    }
}

require_once $publicPath.'/index.php';
