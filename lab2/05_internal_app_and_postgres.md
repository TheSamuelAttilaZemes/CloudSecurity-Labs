# Part 5: Internal Application Path and PostgreSQL

## 1. Purpose of the Demo App

The Nginx plus PHP plus PostgreSQL example exists to show that a database can be available through an application path without being exposed directly on the host.

The request flow is:

1. Browser -> Traefik
2. Traefik -> app-nginx
3. app-nginx -> app-php
4. app-php -> postgres



## 2. Why Nginx Is Included

Nginx is included because it is a realistic application web server.

But it is not acting as the reverse proxy for the whole environment.
Traefik is still the reverse proxy.



## 3. Review the App Files

### 3.1 `app/nginx/default.conf`

This config serves the site and forwards PHP requests to `app-php:9000` with FastCGI.

### 3.2 `app/src/index.php`

This PHP page connects to PostgreSQL using PDO and displays the contents of the `notes` table.

### 3.3 `app/php/Dockerfile`

This installs the PostgreSQL PHP extension so the PHP-FPM container can connect to PostgreSQL.



## 4. PostgreSQL Setup from Scratch

Connect to PostgreSQL from inside the running container:

```bash
docker compose exec postgres psql -U labuser -d labdb
```

Create the table and sample rows:

```sql
CREATE TABLE notes (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    detail TEXT NOT NULL
);

INSERT INTO notes (title, detail)
VALUES
('reverse-proxy', 'Traefik is the only reverse proxy in this lab.'),
('internal-db', 'PostgreSQL is reachable only through the application path.');

SELECT * FROM notes;
```

Exit:

```sql
\q
```



## 5. Open the Demo App

From the physical host, open:

```text
https://localhost:8443/app
```

The page should show:

* that Traefik is the reverse proxy
* that Nginx is the application web server
* the database connection status
* the rows from the `notes` table



## 6. Prove the Database Is Not Published to the Host

Check listening sockets on the VM:

```bash
ss -ltnp
```

You should not see a host listener on port `5432` created by Docker for PostgreSQL.

Test anyway:

```bash
nc -vz 127.0.0.1 5432 || true
```

This should fail because PostgreSQL is not published to the host.



## 7. Prove the Application Can Still Reach PostgreSQL

Test directly from the PHP container:

```bash
docker compose exec app-php php -r '$pdo=new PDO("pgsql:host=postgres;port=5432;dbname=".getenv("POSTGRES_DB"), getenv("POSTGRES_USER"), getenv("POSTGRES_PASSWORD")); echo "DB OK\n";'
```

That should print `DB OK`.



## 8. Check Network Membership

Inspect the networks:

```bash
docker network inspect frontend_net
docker network inspect backend_net
```

Direct checks:

```bash
docker inspect traefik --format '{{json .NetworkSettings.Networks}}'
docker inspect app-nginx --format '{{json .NetworkSettings.Networks}}'
docker inspect app-php --format '{{json .NetworkSettings.Networks}}'
docker inspect postgres --format '{{json .NetworkSettings.Networks}}'
```

Expected result:

* Traefik is on `frontend_net` only
* PostgreSQL is on `backend_net` only
* app-nginx bridges the two networks
* app-php is on `backend_net`



## 9. DNS Resolution Checks Inside Containers

From Traefik:

```bash
docker compose exec traefik sh -lc 'getent hosts postgres || true'
```

That should fail.

From app-nginx:

```bash
docker compose exec app-nginx sh -lc 'getent hosts app-php && getent hosts postgres'
```

That should succeed.

From app-php:

```bash
docker compose exec app-php sh -lc 'getent hosts postgres'
```

That should also succeed.



## 10. Exercises

1. Create the `notes` table and verify that the rows appear at `https://localhost:8443/app`.
2. Explain why app-nginx is on both networks while app-php is only on `backend_net`.
3. Explain why `nc -vz 127.0.0.1 5432` fails while the PHP container can still connect to PostgreSQL.
4. Temporarily publish PostgreSQL with a `ports` section, recreate the stack, and explain what security boundary has changed.
5. Remove the published PostgreSQL port again and confirm that the host can no longer connect directly.
