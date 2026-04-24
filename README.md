# Безхвостов

У нас кейс: умный трекер на хакатоне.

Приложение **«Безхвостов»** связывает учеников, учителей и родителей, помогает закрывать долги (хвосты), хранить и фильтровать информацию, чтобы обучение становилось легче.

## Что умеет

- Трекер задач с глубокой AI-интеграцией:
  - голосовой ввод задач;
  - создание/обновление задач по фото;
  - автоматическая декомпозиция на подзадачи.
- Заметки и папки:
  - хранение знаний;
  - фильтрация и структурирование информации.
- Realtime-звонки (ученик ↔ учитель):
  - помощь в отработке заданий и закрытии хвостов;
  - анализ звонков и сохранение инсайтов в истории.

## Технологии

- Laravel 12
- Inertia + Vue 3
- PostgreSQL
- Redis (очереди/кэш)
- Laravel Reverb (WebSocket)
- Meilisearch (опционально)
- MoonShine (админка)
- S3-совместимое хранилище (загрузка файлов)

---

## Запуск через Docker

На хосте нужны только Docker и Docker Compose.  
PHP, Composer и Node на хосте не требуются.

### Файлы Compose в проекте

| Файл | Назначение |
|---|---|
| `docker-compose.yml` | Основной запуск (app/reverb/queue + PostgreSQL primary/replica/haproxy + Redis + Meilisearch + whisper) |
| `docker-compose.dev.yml` | Dev-режим с bind-mount кода и томами `vendor/node_modules` для быстрой разработки |

### 1) Подготовка

```bash
cp .env.example .env
```

Заполните ключевые переменные в `.env`:

- База данных:
```env
DB_CONNECTION=pgsql
DB_HOST=postgres-haproxy
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=postgres
DB_PASSWORD=secret
```

- Redis / Queue:
```env
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

- Reverb:
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=885140
REVERB_APP_KEY=your_key
REVERB_APP_SECRET=your_secret
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

- S3 (если используете загрузки в облако):
```env
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=...
```

### 2) APP_KEY

```bash
docker compose run --rm app php artisan key:generate --show
```

Скопируйте результат в `.env`:

```env
APP_KEY=base64:...
```

### 3) Сборка и запуск

Основной compose:

```bash
docker compose build app
docker compose up -d
```

Dev compose:

```bash
docker compose -f docker-compose.dev.yml build app
docker compose -f docker-compose.dev.yml up -d
```

### 4) Миграции

```bash
docker compose exec app php artisan migrate --force
```

Опционально:

```bash
docker compose exec app php artisan db:seed --force
```

### 5) Права на `storage` и `bootstrap/cache` (если нужно)

```bash
docker compose exec app chown -R www-data:www-data /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/wix/todo/storage /var/www/wix/todo/bootstrap/cache
```

### 6) Полезные команды

Логи:

```bash
docker compose logs -f app
docker compose logs -f reverb
docker compose logs -f queue
```

Остановка:

```bash
docker compose down
```

Остановка с удалением томов:

```bash
docker compose down -v
```

---

## Локальная разработка без Docker

Нужно локально:

- PHP 8.4+
- Composer
- Node.js + npm
- PostgreSQL
- Redis

### Шаги

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
php artisan reverb:start
php artisan queue:work
```

Для фронтенд-разработки с HMR:

```bash
npm run dev
```
