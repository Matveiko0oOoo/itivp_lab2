<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Auth
{
	public static function requireApiKey(PDO $pdo): void
	{
		$headers = function_exists('getallheaders') ? getallheaders() : [];
		$apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? null;
		if (!$apiKey) {
			self::unauthorized('Missing API key');
		}

		$stmt = $pdo->prepare('SELECT api_key_hash, is_active FROM api_keys WHERE is_active = 1');
		$stmt->execute();
		$keys = $stmt->fetchAll();
		$ok = false;
		foreach ($keys as $row) {
			if (password_verify($apiKey, $row['api_key_hash'])) {
				$ok = true; break;
			}
		}
		if (!$ok) {
			self::unauthorized('Invalid API key');
		}
	}

	private static function unauthorized(string $message): void
	{
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode(['error' => $message]);
		exit;
	}
}
