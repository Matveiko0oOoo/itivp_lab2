<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Auth;
use App\Models\VacancyModel;

require_once __DIR__ . '/../src/config/Database.php';
require_once __DIR__ . '/../src/config/Auth.php';
require_once __DIR__ . '/../src/models/VacancyModel.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

if ($path === '/' || $path === '/ui') {
	header('Content-Type: text/html; charset=utf-8');
	readfile(__DIR__ . '/ui.html');
	exit;
}

if (strpos($path, '/api') !== 0) {
	http_response_code(404);
	echo json_encode(['error' => 'Not found']);
	exit;
}

$pdo = App\Config\Database::getConnection();

App\Config\Auth::requireApiKey($pdo);

$segments = array_values(array_filter(explode('/', $path)));

if (count($segments) >= 2 && $segments[0] === 'api' && $segments[1] === 'vacancies') {
	$model = new App\Models\VacancyModel($pdo);

	$readJson = function (): array {
		$raw = file_get_contents('php://input');
		$data = json_decode($raw ?: '[]', true);
		if (!is_array($data)) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid JSON']);
			exit;
		}
		return $data;
	};

	if ($method === 'GET' && count($segments) === 2) {
		$rows = $model->getAll();
		echo json_encode($rows);
		exit;
	}

	if ($method === 'GET' && count($segments) === 3) {
		$id = (int)$segments[2];
		if ($id <= 0) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid id']);
			exit;
		}
		$row = $model->getById($id);
		if (!$row) {
			http_response_code(404);
			echo json_encode(['error' => 'Not found']);
			exit;
		}
		echo json_encode($row);
		exit;
	}

	if ($method === 'POST' && count($segments) === 2) {
		$data = $readJson();
		$title = isset($data['title']) && is_string($data['title']) ? trim($data['title']) : '';
		if ($title === '') {
			http_response_code(400);
			echo json_encode(['error' => 'Field title is required']);
			exit;
		}
		$payload = [
			'title' => $title,
			'description' => isset($data['description']) ? (string)$data['description'] : null,
			'requirements' => isset($data['requirements']) ? (string)$data['requirements'] : null,
			'salary_range' => isset($data['salary_range']) ? (string)$data['salary_range'] : null,
		];
		$id = $model->create($payload);
		http_response_code(201);
		echo json_encode(['id' => $id] + $payload);
		exit;
	}

	if (($method === 'PUT' || $method === 'PATCH') && count($segments) === 3) {
		$id = (int)$segments[2];
		if ($id <= 0) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid id']);
			exit;
		}
		if (!$model->getById($id)) {
			http_response_code(404);
			echo json_encode(['error' => 'Not found']);
			exit;
		}
		$data = $readJson();
		$title = isset($data['title']) && is_string($data['title']) ? trim($data['title']) : '';
		if ($title === '') {
			http_response_code(400);
			echo json_encode(['error' => 'Field title is required']);
			exit;
		}
		$payload = [
			'title' => $title,
			'description' => isset($data['description']) ? (string)$data['description'] : null,
			'requirements' => isset($data['requirements']) ? (string)$data['requirements'] : null,
			'salary_range' => isset($data['salary_range']) ? (string)$data['salary_range'] : null,
		];
		$model->update($id, $payload);
		echo json_encode(['id' => $id] + $payload);
		exit;
	}

	if ($method === 'DELETE' && count($segments) === 3) {
		$id = (int)$segments[2];
		if ($id <= 0) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid id']);
			exit;
		}
		if (!$model->getById($id)) {
			http_response_code(404);
			echo json_encode(['error' => 'Not found']);
			exit;
		}
		$model->delete($id);
		echo json_encode(['status' => 'deleted', 'id' => $id]);
		exit;
	}
}

http_response_code(404);

echo json_encode(['error' => 'Route not found']);
