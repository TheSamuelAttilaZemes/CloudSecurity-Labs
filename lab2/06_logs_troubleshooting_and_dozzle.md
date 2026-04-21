# Part 6: Logs, Troubleshooting, and Dozzle

## 1. Why This Section Matters

A working reverse proxy setup should still be inspected and troubleshot systematically.

This lab uses two complementary approaches:

* terminal-based troubleshooting
* Dozzle for quick log viewing

Dozzle is helpful, but it is not a replacement for shell access, `docker logs`, or `docker compose logs`.



## 2. Start with Container Status

Always begin with:

```bash
docker compose ps
```

This tells you which containers are up, restarting, or exited.



## 3. Use `docker compose logs`

Examples:

```bash
docker compose logs traefik --tail=100
docker compose logs dozzle --tail=50
docker compose logs webgoat --tail=50
docker compose logs juice-shop --tail=50
docker compose logs app-nginx --tail=50
docker compose logs app-php --tail=50
docker compose logs postgres --tail=50
```

This is usually the best starting point because it is service-aware and works directly with the Compose project.



## 4. Use `docker logs`

This works with a specific container name.

Examples:

```bash
docker logs -f traefik
docker logs -f dozzle
```

This is useful when you want to follow one container continuously.



## 5. Use `curl` for Fast Route Testing

Examples:

```bash
curl -k https://localhost:8443/dashboard/ | head
curl -k https://localhost:8443/dozzle/ | head
curl -k -L -o /dev/null -w 'WebGoat: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebGoat/
curl -k -L -o /dev/null -w 'WebWolf: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebWolf/
curl -k -I https://localhost:8443/juice
curl -k https://localhost:8443/app | head
```

This is a fast way to distinguish routing problems from browser-specific behaviour.



## 6. Use Shell Access Inside Containers

Examples:

```bash
docker compose exec traefik sh
docker compose exec app-nginx sh
docker compose exec app-php sh
docker compose exec postgres sh
docker compose exec webgoat sh
```

Useful checks from inside a container:

```bash
ls -l /certs
cat /etc/traefik/traefik.yml
printenv | sort
getent hosts postgres
getent hosts app-php
wget -S -O- http://127.0.0.1:8080/WebGoat 2>&1 | head -40
wget -S -O- http://127.0.0.1:9090/WebWolf 2>&1 | head -40
```



## 7. Use Dozzle

Open:

```text
https://localhost:8443/dozzle/
```

Dozzle reads Docker logs through the Docker socket and provides a quick browser view of live container output.

This is useful for:

* fast visual inspection
* comparing multiple containers
* seeing live output during tests

But it should always be paired with terminal-based work.



## 8. The Docker Socket Mount

Dozzle and Traefik both mount:

```yaml
/var/run/docker.sock:/var/run/docker.sock:ro
```

Important points:

* `:ro` means read-only, which is safer than read-write
* even read-only access to the Docker socket is sensitive

That should be discussed as part of the security model rather than treated as invisible background plumbing.



## 9. Example Troubleshooting Sequence

A good order is:

1. `docker compose ps`
2. `docker compose logs <service>`
3. `curl` route tests
4. Traefik live API checks with `/api/http/routers` and `/api/http/services`
5. `docker inspect` for labels and networks
6. `docker compose exec <service> sh`
7. run DNS, env, file, or application checks inside the container

This keeps troubleshooting structured.



## 10. Common Problems

### 10.1 Browser shows certificate warning

Expected with a self-signed certificate.

### 10.2 Traefik route returns 404

Often means the router rule did not match the request, or the route was not loaded.

### 10.3 Traefik route returns 502 or 504

Often means the router matched, but Traefik could not reach the backend service on the expected port or network.

### 10.4 App page loads but DB section fails

Check PostgreSQL logs, environment variables, and backend network membership.

### 10.5 Service container is running but app still does not work

`depends_on` does not guarantee readiness.

### 10.6 WebGoat or WebWolf appears to fail under simple `curl` testing

Remember that these routes redirect before reaching the login page. Use `curl -L` and inspect the final URL and HTTP code.



## 11. Exercises

1. Use `docker compose logs` to inspect Traefik and identify the log lines that show it has started successfully.
2. Use Dozzle to view logs for at least two containers and compare that experience with the terminal.
3. Use the Traefik API to inspect the live routers and services and identify which are loaded from Docker labels and which are loaded from the file provider.
4. Use `docker compose exec` to enter the Traefik container and verify that the certificate files and dynamic config files exist.
5. Use the internal `wget` tests inside the WebGoat container and explain why they are useful when diagnosing reverse-proxy issues.
