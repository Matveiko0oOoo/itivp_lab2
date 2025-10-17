<?php
declare(strict_types=1);

$env = getenv('APP_ENV') ?: 'production';
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'vacancies_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$outputPath = getenv('API_KEY_OUTPUT_PATH') ?: __DIR__ . '/../.generated_api_key.txt';

try {
	$pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
} catch (Throwable $e) {
	fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
	exit(0); // Do not crash container
}

function generateToken(int $length = 48): string {
	$bytes = random_bytes((int)ceil($length / 2));
	return substr(bin2hex($bytes), 0, $length);
}

// Check current active keys
$count = (int)$pdo->query('SELECT COUNT(*) FROM api_keys WHERE is_active = 1')->fetchColumn();

$shouldGenerateNew = false;
if ($count === 0) {
	$shouldGenerateNew = true;
} else {
	// If output file missing, rotate a new key
	if (!file_exists($outputPath)) {
		$shouldGenerateNew = true;
	}
}

if ($shouldGenerateNew) {
	// Deactivate all existing keys to keep a single active
	$pdo->exec('UPDATE api_keys SET is_active = 0 WHERE is_active = 1');

	$plain = generateToken(48);
	$hash = password_hash($plain, PASSWORD_DEFAULT);
	$stmt = $pdo->prepare('INSERT INTO api_keys (api_key_hash, user_id, is_active) VALUES (:hash, :user_id, 1)');
	$stmt->execute([
		':hash' => $hash,
		':user_id' => null,
	]);

	file_put_contents($outputPath, "API_KEY={$plain}\n");
	chmod($outputPath, 0600);
	echo "Generated new API key written to {$outputPath}\n";
	exit(0);
}

// Nothing to do, but ensure file exists with a hint if it was deleted
if (!file_exists($outputPath)) {
	file_put_contents($outputPath, "# No plaintext available for existing keys.\n# To rotate a new key, delete this file and restart container.\n");
	chmod($outputPath, 0600);
}

echo "API key already exists. Skipping seed.\n";
