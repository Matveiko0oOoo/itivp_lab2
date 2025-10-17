# RESTful API на PHP для управления вакансиями

Простой полнофункциональный REST API (CRUD) для сущности `vacancies` с аутентификацией по API‑ключу, форматом JSON, корректными HTTP‑статусами, Docker‑окружением (PHP‑Apache + MySQL), минимальным UI и коллекцией Postman.

## Стек
- PHP 8.2 (Apache)
- MySQL 8
- PDO (mysql)
- Docker / Docker Compose

## Архитектура проекта
```
.
├─ Dockerfile
├─ docker-compose.yml
├─ docker-entrypoint.sh
├─ .env.example
├─ scripts/
│  ├─ init.sql                # Создание таблиц api_keys, vacancies
│  └─ seed_api_key.php        # Генерация API-ключа (однократно)
├─ public/
│  ├─ .htaccess               # Маршрутизация на index.php
│  ├─ health                  # Healthcheck
│  ├─ index.php               # Точка входа API и UI
│  └─ ui.html                 # Минимальный UI для демонстрации
├─ src/
│  ├─ config/
│  │  ├─ Database.php         # Подключение к БД (PDO)
│  │  └─ Auth.php             # Проверка X-API-Key
│  └─ models/
│     └─ VacancyModel.php     # CRUD для таблицы vacancies
└─ postman/
   └─ VacanciesAPI.postman_collection.json
```

## База данных
- БД: `vacancies_db` (создаётся контейнером MySQL)
- Таблицы:
  - `api_keys(id, api_key_hash, user_id, is_active, created_at)`
  - `vacancies(id, title, description, requirements, salary_range, created_at, updated_at)`

`api_keys.api_key_hash` хранит хеш ключа (через `password_hash`). Сам plaintext ключ генерируется при первом запуске и записывается в файл `.generated_api_key.txt` внутри контейнера (он проброшен в корень проекта).

## Быстрый старт (Docker)
1. Установите Docker Desktop.
2. В корне проекта выполните:
```bash
docker compose up --build
```
3. Дождитесь, когда контейнеры поднимутся. В логах приложения появится строка вида:
```
Generated API key written to /var/www/html/.generated_api_key.txt
```
4. Посмотрите сгенерированный ключ в файле `./.generated_api_key.txt` (рядом с проектом). Скопируйте значение после `API_KEY=`.

- UI доступен по адресу: `http://localhost:8080/`
- API базовый URL: `http://localhost:8080`
- MySQL доступен на порту `33060` (локально)

Остановка:
```bash
docker compose down
```

## Аутентификация
Каждый запрос к `/api/*` должен содержать заголовок:
```
X-API-Key: <ВАШ_КЛЮЧ>
```
Хранится и проверяется только хеш ключа. При компрометации ключ можно деактивировать (перевести `is_active=0`) или удалить из БД и сгенерировать новый.

## Эндпоинты
База: `http://localhost:8080`

- GET `/api/vacancies` — получить все вакансии
- GET `/api/vacancies/{id}` — получить вакансию по `id`
- POST `/api/vacancies` — создать вакансию
- PUT `/api/vacancies/{id}` — обновить вакансию
- DELETE `/api/vacancies/{id}` — удалить вакансию

Тело запроса и ответы — JSON. Обязательное поле при создании/обновлении: `title` (string).

### Примеры запросов (curl)
```bash
# Все
curl -H "X-API-Key: $API_KEY" http://localhost:8080/api/vacancies

# Одна запись
curl -H "X-API-Key: $API_KEY" http://localhost:8080/api/vacancies/1

# Создать
curl -X POST -H "Content-Type: application/json" -H "X-API-Key: $API_KEY" \
  -d '{"title":"Backend Dev","description":"PHP","requirements":"3+ years","salary_range":"$2000-$3000"}' \
  http://localhost:8080/api/vacancies

# Обновить
curl -X PUT -H "Content-Type: application/json" -H "X-API-Key: $API_KEY" \
  -d '{"title":"Senior Backend Dev","description":"PHP, Docker","requirements":"5+ years","salary_range":"$3000-$4000"}' \
  http://localhost:8080/api/vacancies/1

# Удалить
curl -X DELETE -H "X-API-Key: $API_KEY" http://localhost:8080/api/vacancies/1
```

## Минимальный UI
Перейдите на `http://localhost:8080/`, вставьте свой `X-API-Key` и протестируйте CRUD через кнопки формы.

## Postman
Импортируйте коллекцию из `postman/VacanciesAPI.postman_collection.json`.
- Переменная `base_url`: `http://localhost:8080`
- Переменная `api_key`: вставьте ваш ключ

## Валидация и безопасность
- Все SQL — через prepared statements.
- Все ответы — JSON с корректными HTTP‑кодами: 200/201/400/401/404/500.
- Проверка заголовка `X-API-Key` для всех `/api/*` эндпоинтов.
- Ключи хранятся в виде хешей (`password_hash`/`password_verify`).

## Переменные окружения
Используются из docker-compose, но можно задать вручную (см. `.env.example`):
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_ENV`
- `API_KEY_OUTPUT_PATH` (путь для записи сгенерированного ключа)

## Разработка без Docker (необязательно)
1. Создайте БД и таблицы из `scripts/init.sql`.
2. Настройте виртуальный хост Apache на `public/` и включите `mod_rewrite`.
3. Укажите переменные окружения (или пропишите в код при необходимости).
4. Запустите `php scripts/seed_api_key.php` для генерации ключа.

## Лицензия
MIT
