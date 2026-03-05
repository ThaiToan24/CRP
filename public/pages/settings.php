<?php
/**
 * Settings Redirect
 * Redirects to account page
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);

require_once __DIR__ . '/../../src/utils/Url.php';
if (!$auth->isLoggedIn()) {
    redirect('auth/login.php');
}

redirect('pages/account.php');
