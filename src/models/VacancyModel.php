<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class VacancyModel
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function getAll(): array
	{
		$stmt = $this->pdo->query('SELECT id, title, description, requirements, salary_range, created_at, updated_at FROM vacancies ORDER BY id DESC');
		return $stmt->fetchAll();
	}

	public function getById(int $id): ?array
	{
		$stmt = $this->pdo->prepare('SELECT id, title, description, requirements, salary_range, created_at, updated_at FROM vacancies WHERE id = :id');
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->pdo->prepare('INSERT INTO vacancies (title, description, requirements, salary_range) VALUES (:title, :description, :requirements, :salary_range)');
		$stmt->execute([
			':title' => $data['title'],
			':description' => $data['description'] ?? null,
			':requirements' => $data['requirements'] ?? null,
			':salary_range' => $data['salary_range'] ?? null,
		]);
		return (int)$this->pdo->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$stmt = $this->pdo->prepare('UPDATE vacancies SET title = :title, description = :description, requirements = :requirements, salary_range = :salary_range WHERE id = :id');
		return $stmt->execute([
			':title' => $data['title'],
			':description' => $data['description'] ?? null,
			':requirements' => $data['requirements'] ?? null,
			':salary_range' => $data['salary_range'] ?? null,
			':id' => $id,
		]);
	}

	public function delete(int $id): bool
	{
		$stmt = $this->pdo->prepare('DELETE FROM vacancies WHERE id = :id');
		return $stmt->execute([':id' => $id]);
	}
}
