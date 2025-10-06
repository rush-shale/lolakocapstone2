<?php
require_once __DIR__ . '/config.php';

function get_db_connection(): PDO {
	static $pdo = null;
	if ($pdo instanceof PDO) {
		// Test if connection is still alive
		try {
			$pdo->query('SELECT 1');
			return $pdo;
		} catch (PDOException $e) {
			// Connection is dead, create a new one
			$pdo = null;
		}
	}
	
	$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
		PDO::ATTR_TIMEOUT => 30, // 30 second timeout
		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
	];
	
	try {
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		return $pdo;
	} catch (PDOException $e) {
		error_log("Database connection failed: " . $e->getMessage());
		throw new Exception("Database connection failed. Please try again.");
	}
}

function safe_rollback(PDO $pdo): bool {
	try {
		if ($pdo->inTransaction()) {
			$pdo->rollback();
		}
		return true;
	} catch (PDOException $e) {
		error_log("Rollback failed: " . $e->getMessage());
		return false;
	}
}
?>


