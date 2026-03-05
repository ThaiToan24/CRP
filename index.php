<?php
// Serve the public web root directly to avoid 404s from redirects
require_once __DIR__ . '/public/index.php';
exit();
