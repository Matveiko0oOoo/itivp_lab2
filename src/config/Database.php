<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
	public static function getConnection(): PDO
	{
		$host = getenv('DB_HOST') ?: 'localhost';
		$port = getenv('DB_PORT') ?: '3306';
		$name = getenv('DB_NAME') ?: 'vacancies_db';
		$user = getenv('DB_USER') ?: 'root';
		$pass = getenv('DB_PASS') ?: '';

		$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
		try {
			$pdo = new PDO($dsn, $user, $pass, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]);
			return $pdo;
		} catch (PDOException $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Database connection failed']);
			exit;
		}
	}
}
