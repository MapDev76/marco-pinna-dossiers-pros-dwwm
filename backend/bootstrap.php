<?php

// Bootstrap file: loads the shared dependencies before controllers and views.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

date_default_timezone_set(appTimezoneName());

/**
 * Starts the session early so controllers and views can safely use flash data and auth helpers.
 */

if (!function_exists('startAppSession')) {
	// Keep the session bootstrap local and reusable in case the helper is loaded elsewhere.
	function startAppSession(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}
}

startAppSession();
