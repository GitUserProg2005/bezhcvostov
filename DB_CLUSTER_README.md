# Кластер PostgreSQL (repmgr + HAProxy)

Сервисы в `docker-compose.yml`: `postgres-primary`, `postgres-replica`, `postgres-haproxy`. Приложение ходит в БД через **HAProxy** (`DB_HOST=postgres-haproxy`, с хоста порт **5432**). Прямой доступ к реплике с хоста: **5433**.

Пароли по умолчанию в compose: пользователь `postgres`, пароль `secret`; роль repmgr — пароль из `REPMGR_PASSWORD` (`repmgrpass`).

---

## Тест кластера repmgr

Ниже команды с хоста; при других именах контейнеров или паролях подставьте свои значения.

### 1. Кто primary, кто standby

Ожидание: на одном узле `f` (не в recovery = primary), на другом `t` (standby).

```bash
PGPASSWORD=secret docker exec postgres-primary psql -U postgres -d postgres -c "select pg_is_in_recovery();"
PGPASSWORD=secret docker exec postgres-replica psql -U postgres -d postgres -c "select pg_is_in_recovery();"
```

Через HAProxy (должен попасть только в текущий primary):

```bash
PGPASSWORD=secret psql -h 127.0.0.1 -p 5432 -U postgres -d postgres -c "select pg_is_in_recovery();"
```

### 2. Состояние узлов в repmgr

```bash
PGPASSWORD=secret docker exec postgres-primary psql -U postgres -d repmgr -c \
  "select node_id, node_name, active, type, upstream_node_id from repmgr.nodes order by node_id;"
```

После failover ту же проверку имеет смысл выполнить **на контейнере, который стал новым primary** (или с обоих, если оба подняты).

### 3. Репликация (должны быть строки при работающем standby)

На primary:

```bash
PGPASSWORD=secret docker exec postgres-primary psql -U postgres -d postgres -c "select * from pg_stat_replication;"
```

На standby:

```bash
PGPASSWORD=secret docker exec postgres-replica psql -U postgres -d postgres -c "select * from pg_stat_wal_receiver;"
```

### 4. Обзор кластера (CLI repmgr, если доступен в образе)

```bash
docker exec postgres-primary bash -lc \
  'PGPASSWORD=repmgrpass repmgr -h localhost -U repmgr -d repmgr cluster show'
```

При ошибке подключения проверьте путь к конфигу в образе Bitnami или используйте только SQL из пунктов 1–3.

### 5. Проверка failover (остановка текущего primary)

1. По пункту 1 выясните, у какого контейнера `pg_is_in_recovery = f` — это **текущий primary**.
2. Остановите **только его** (не используйте `docker compose down` — опустится весь проект):

   ```bash
   docker stop postgres-primary
   # или, если primary оказался на реплике:
   # docker stop postgres-replica
   ```

3. Подождать 15–30 секунд (repmgr/HAProxy переключатся).

4. Снова пункты 1–3: новый primary должен дать `f`, в `repmgr.nodes` — один активный primary и корректный standby/upstream, `pg_stat_replication` на новом primary — непустой при живой реплике.

5. Запуск старого узла (если нужен полный кластер):

   ```bash
   docker start postgres-primary
   ```

   Дальше может потребоваться привести бывший primary обратно в роль standby (rejoin) — зависит от политики и документации образа; при сомнениях проще пересоздать тома dev-кластера.

### 6. Если «два primary» и пустая репликация

Это split-brain после экспериментов. Для локальной среды обычно: `docker compose down`, удаление томов `postgres_primary_data` и `postgres_replica_data`, затем `docker compose up -d` и заново миграции.

---

## Полезно помнить

- Останавливать для теста failover нужно контейнер **текущего** primary, а не «всегда postgres-primary» по имени.
- HAProxy направляет трафик только на узел, у которого `pg_is_in_recovery()` даёт `f`.
