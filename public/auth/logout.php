<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/utils/Auth.php';

$auth = new Auth($db);
$auth->logout();

// Ensure session data and cookie are cleared
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}
session_destroy();

// Redirect to public home
require_once __DIR__ . '/../../src/utils/Url.php';
redirect('pages/home.php');
