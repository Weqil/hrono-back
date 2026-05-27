# Docker Config `hrono_nginx` для Swarm

Форма **Configs** в Swarmpit часто падает с `Content-Type text/plain` при вставке `swarm.conf`.
Так и создают config в проде — через **Docker CLI** или **API** (JSON).

## Способ 1 — на manager-ноде (как обычно в Swarm)

```bash
docker config create hrono_nginx /path/to/docker/nginx/swarm.conf
docker config ls
```

Обновление конфига (имя менять нельзя — создают новый):

```bash
docker config rm hrono_nginx
docker config create hrono_nginx /path/to/docker/nginx/swarm.conf
```

После этого в Swarmpit: **Stacks** → deploy `docker-compose.swarm.yml`.

## Способ 2 — JSON в Swarmpit API

Файл `hrono_nginx.swarmpit.json` — тело запроса с `Content-Type: application/json`.

Пример (подставьте URL и токен Swarmpit):

```bash
curl -X POST "https://SWARMPIT_HOST/api/configs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d @docker/nginx/hrono_nginx.swarmpit.json
```

## Способ 3 — с Mac на сервер

```bash
ssh user@SWARM_MANAGER 'docker config create hrono_nginx -' \
  < docker/nginx/swarm.conf
```

## В UI Swarmpit

Не вставляйте содержимое `swarm.conf` в поле, которое шлёт plain text.
Если есть только текстовое поле без JSON — используйте способ 1 или 3.
