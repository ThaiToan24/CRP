<?php
/**
 * URL helper utilities
 *
 * - getBaseUrl(): returns the base URL (including /public if the project is
 *   accessed via a subdirectory) for constructing links and redirects.
 * - redirect(): sends a Location header composed from the base URL and a
 *   relative path, then exits.
 */

function getBaseUrl() {
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $pos = strpos($script, '/public');
    // always include "/public" so links work even when doc root is set to the public folder
    if ($pos !== false) {
        $path = substr($script, 0, $pos + 7);
    } else {
        $path = '/public';
    }
    $base = $protocol . '://' . $host . $path;
    return $base;
}

function redirect($relative) {
    $base = getBaseUrl();
    // ensure a single slash between base and relative
    $url = rtrim($base, '/') . '/' . ltrim($relative, '/');
    header('Location: ' . $url);
    exit();
}
