<?php

// Fichier d'amorçage : charge les dépendances communes avant les contrôleurs et les vues.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

/**
 * Initialisation de la session
 * Assure que la session est démarrée avant toute manipulation des données de session.
 * Permet de stocker des informations sur l'utilisateur connecté et les messages flash.
 */

if (!function_exists('startAppSession')) {
	function startAppSession(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}
}

startAppSession();
