# Part 2: Add Keycloak and Its Database

## 1. Overview

This part adds Keycloak and its dedicated PostgreSQL database to the existing Lab 2 stack.

At the end of this part, the environment should have:

* Keycloak running in a container
* a PostgreSQL database dedicated to Keycloak
* Keycloak reachable through Traefik at `https://localhost:8443/keycloak/`

---

## 2. Important Baseline Assumption

This part assumes the final working version of Lab 2 is already in place.

Keep these existing Lab 2 files:

* `traefik/traefik.yml`
* `traefik/dynamic/tls.yml`
* `traefik/dynamic/webgoat.yml`

Lab 3 adds a new dynamic file rather than replacing the existing Lab 2 dynamic files.

The new file introduced in Lab 3 will be:

* `traefik/dynamic/lab3-keycloak-routes.yml`

---

## 3. Update `.env`

Add the following variables to the existing `.env` file from Lab 2.

These values are for lab use only and should be changed if the environment is reused elsewhere.

```dotenv
# Existing Lab 2 variables remain in place
POSTGRES_DB=labdb
POSTGRES_USER=labuser
POSTGRES_PASSWORD=change_this_for_local_lab_use

# Keycloak database settings
KEYCLOAK_DB=keycloak
KEYCLOAK_DB_USER=keycloak
KEYCLOAK_DB_PASSWORD=change_this_keycloak_db_password

# Bootstrap admin account for first login
KEYCLOAK_ADMIN_USER=admin
KEYCLOAK_ADMIN_PASSWORD=change_this_admin_password

# Shared cookie secret used by the OAuth2 Proxy containers
OAUTH2_PROXY_COOKIE_SECRET=0123456789abcdef0123456789abcdef
```

The existing Lab 2 demo app continues to use the original PostgreSQL settings.
The new Keycloak database settings are separate and are used only by Keycloak.

---

## 4. Add a New Docker Network

Lab 2 already used:

* `frontend_net`
* `backend_net`

Lab 3 adds:

* `keycloak_net`

This extra network makes the path between Keycloak and its own database easier to understand.

---

## 5. Full Updated `docker-compose.yml`

Use this file as the new Lab 3 baseline. It extends the final working Lab 2 stack rather than replacing it.

```yaml
services:
  traefik:
    image: traefik:v3
    container_name: traefik
    restart: unless-stopped
    ports:
      - '8080:80'
      - '8443:8443'
    volumes:
      - '/var/run/docker.sock:/var/run/docker.sock:ro'
      - './traefik/traefik.yml:/etc/traefik/traefik.yml:ro'
      - './traefik/dynamic:/etc/traefik/dynamic:ro'
      - './certs:/certs:ro'
    networks:
      - frontend_net
    labels:
      - 'traefik.enable=true'
      - 'traefik.http.routers.dashboard.rule=PathPrefix(`/dashboard`) || PathPrefix(`/api`)'
      - 'traefik.http.routers.dashboard.entrypoints=websecure'
      - 'traefik.http.routers.dashboard.tls=true'
      - 'traefik.http.routers.dashboard.service=api@internal'

  dozzle:
    image: amir20/dozzle:latest
    container_name: dozzle
    restart: unless-stopped
    command:
      - '--base=/dozzle'
    volumes:
      - '/var/run/docker.sock:/var/run/docker.sock:ro'
    networks:
      - frontend_net
    labels:
      - 'traefik.enable=true'
      - 'traefik.docker.network=frontend_net'
      - 'traefik.http.routers.dozzle.rule=PathPrefix(`/dozzle`)'
      - 'traefik.http.routers.dozzle.entrypoints=websecure'
      - 'traefik.http.routers.dozzle.tls=true'
      - 'traefik.http.routers.dozzle.middlewares=dozzle-ratelimit'
      - 'traefik.http.services.dozzle.loadbalancer.server.port=8080'
      - 'traefik.http.middlewares.dozzle-ratelimit.ratelimit.average=5'
      - 'traefik.http.middlewares.dozzle-ratelimit.ratelimit.burst=10'

  webgoat:
    image: webgoat/webgoat:latest
    container_name: webgoat
    restart: unless-stopped
    networks:
      - frontend_net

  juice-shop:
    image: bkimminich/juice-shop:latest
    container_name: juice-shop
    restart: unless-stopped
    environment:
      BASE_PATH: /juice
    networks:
      - frontend_net
    labels:
      - 'traefik.enable=true'
      - 'traefik.docker.network=frontend_net'
      - 'traefik.http.routers.juice.rule=PathPrefix(`/juice`)'
      - 'traefik.http.routers.juice.entrypoints=websecure'
      - 'traefik.http.routers.juice.tls=true'
      - 'traefik.http.services.juice.loadbalancer.server.port=3000'

  app-nginx:
    image: nginx:latest
    container_name: app-nginx
    restart: unless-stopped
    depends_on:
      - app-php
    volumes:
      - './app/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro'
      - './app/src:/var/www/html:ro'
    networks:
      - frontend_net
      - backend_net
    labels:
      - 'traefik.enable=true'
      - 'traefik.docker.network=frontend_net'
      - 'traefik.http.routers.app.rule=PathPrefix(`/app`)'
      - 'traefik.http.routers.app.entrypoints=websecure'
      - 'traefik.http.routers.app.tls=true'
      - 'traefik.http.routers.app.middlewares=app-strip'
      - 'traefik.http.middlewares.app-strip.stripprefix.prefixes=/app'
      - 'traefik.http.services.app.loadbalancer.server.port=80'

  app-php:
    build:
      context: ./app/php
    container_name: app-php
    restart: unless-stopped
    env_file:
      - .env
    depends_on:
      - postgres
    volumes:
      - './app/src:/var/www/html:ro'
    networks:
      - backend_net

  postgres:
    image: postgres:18-alpine
    container_name: postgres
    restart: unless-stopped
    env_file:
      - .env
    volumes:
      - 'pgdata:/var/lib/postgresql/data'
    networks:
      - backend_net

  keycloak-db:
    image: postgres:18-alpine
    container_name: keycloak-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${KEYCLOAK_DB}
      POSTGRES_USER: ${KEYCLOAK_DB_USER}
      POSTGRES_PASSWORD: ${KEYCLOAK_DB_PASSWORD}
    volumes:
      - 'keycloak_pgdata:/var/lib/postgresql/data'
    networks:
      - keycloak_net

  keycloak:
    image: quay.io/keycloak/keycloak:26.6.0
    container_name: keycloak
    restart: unless-stopped
    command: start-dev
    environment:
      KC_BOOTSTRAP_ADMIN_USERNAME: ${KEYCLOAK_ADMIN_USER}
      KC_BOOTSTRAP_ADMIN_PASSWORD: ${KEYCLOAK_ADMIN_PASSWORD}
      KC_DB: postgres
      KC_DB_URL_HOST: keycloak-db
      KC_DB_URL_DATABASE: ${KEYCLOAK_DB}
      KC_DB_USERNAME: ${KEYCLOAK_DB_USER}
      KC_DB_PASSWORD: ${KEYCLOAK_DB_PASSWORD}
      KC_HTTP_ENABLED: 'true'
      KC_PROXY_HEADERS: xforwarded
      KC_HTTP_RELATIVE_PATH: /keycloak
      KC_HOSTNAME: localhost
      KC_HOSTNAME_PORT: '8443'
      KC_HOSTNAME_STRICT: 'false'
    depends_on:
      - keycloak-db
    networks:
      - frontend_net
      - keycloak_net
    labels:
      - 'traefik.enable=true'
      - 'traefik.docker.network=frontend_net'
      - 'traefik.http.routers.keycloak.rule=PathPrefix(`/keycloak`)'
      - 'traefik.http.routers.keycloak.entrypoints=websecure'
      - 'traefik.http.routers.keycloak.tls=true'
      - 'traefik.http.services.keycloak.loadbalancer.server.port=8080'

  whoami-a:
    image: traefik/whoami:latest
    container_name: whoami-a
    restart: unless-stopped
    networks:
      - frontend_net

  whoami-b:
    image: traefik/whoami:latest
    container_name: whoami-b
    restart: unless-stopped
    networks:
      - frontend_net

  oauth2-proxy-a:
    image: quay.io/oauth2-proxy/oauth2-proxy:v7.15.1
    container_name: oauth2-proxy-a
    restart: unless-stopped
    env_file:
      - .env
    command:
      - --provider=keycloak-oidc
      - --oidc-issuer-url=https://localhost:8443/keycloak/realms/cloudlab
      - --redirect-url=https://localhost:8443/oauth2/a/callback
      - --http-address=0.0.0.0:4180
      - --upstream=http://whoami-a:80/
      - --email-domain=*
      - --cookie-secret=${OAUTH2_PROXY_COOKIE_SECRET}
      - --cookie-secure=true
      - --cookie-samesite=lax
      - --cookie-path=/
      - --scope=openid profile email
      - --client-id=whoami-a-proxy
      - --client-secret=CHANGE_ME_AFTER_CLIENT_CREATION
      - --reverse-proxy=true
      - --whitelist-domain=localhost
      - --skip-provider-button=true
      - --pass-authorization-header=true
      - --pass-access-token=true
      - --set-xauthrequest=true
    networks:
      - frontend_net

  oauth2-proxy-b:
    image: quay.io/oauth2-proxy/oauth2-proxy:v7.15.1
    container_name: oauth2-proxy-b
    restart: unless-stopped
    env_file:
      - .env
    command:
      - --provider=keycloak-oidc
      - --oidc-issuer-url=https://localhost:8443/keycloak/realms/cloudlab
      - --redirect-url=https://localhost:8443/oauth2/b/callback
      - --http-address=0.0.0.0:4181
      - --upstream=http://whoami-b:80/
      - --email-domain=*
      - --cookie-secret=${OAUTH2_PROXY_COOKIE_SECRET}
      - --cookie-secure=true
      - --cookie-samesite=lax
      - --cookie-path=/
      - --scope=openid profile email
      - --client-id=whoami-b-proxy
      - --client-secret=CHANGE_ME_AFTER_CLIENT_CREATION
      - --reverse-proxy=true
      - --whitelist-domain=localhost
      - --skip-provider-button=true
      - --pass-authorization-header=true
      - --pass-access-token=true
      - --set-xauthrequest=true
    networks:
      - frontend_net

networks:
  frontend_net:
    name: frontend_net
  backend_net:
    name: backend_net
    internal: true
  keycloak_net:
    name: keycloak_net

volumes:
  pgdata:
  keycloak_pgdata:
```

---

## 6. Why the OAuth2 Proxy Client Secret Is a Placeholder Initially

The OAuth2 Proxy containers need client IDs and client secrets issued by Keycloak.

Those secrets do not exist until the Keycloak clients are created in a later part of the lab.

So the normal order is:

1. start the Lab 2 base services first so that Traefik and HTTPS are already working
2. start Keycloak and its database
3. create the realm and clients in Keycloak
4. copy the client secrets from the Keycloak admin console
5. replace the placeholder values in `docker-compose.yml`
6. start or recreate the OAuth2 Proxy containers

That ordering is expected and should be understood clearly rather than hidden.

---

## 7. Keep the Existing Traefik Static and TLS Files

The Traefik static configuration from Lab 2 should remain in place.

The same applies to the existing `tls.yml` file and the existing `webgoat.yml` file.

Lab 3 adds a new dynamic file rather than replacing the earlier ones.

---

## 8. New Traefik Dynamic File for Protected Routes

Create this file as:

* `traefik/dynamic/lab3-keycloak-routes.yml`

This file contains the new identity-aware routes introduced in Lab 3.

```yaml
http:
  routers:
    oauth2-a:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/oauth2/a`)"
      service: oauth2-a-svc
      tls: {}

    secure-a:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/secure-a`)"
      middlewares:
        - secure-a-strip
      service: oauth2-a-svc
      tls: {}

    oauth2-b:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/oauth2/b`)"
      service: oauth2-b-svc
      tls: {}

    secure-b:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/secure-b`)"
      middlewares:
        - secure-b-strip
      service: oauth2-b-svc
      tls: {}

  middlewares:
    secure-a-strip:
      stripPrefix:
        prefixes:
          - /secure-a

    secure-b-strip:
      stripPrefix:
        prefixes:
          - /secure-b

  services:
    oauth2-a-svc:
      loadBalancer:
        servers:
          - url: "http://oauth2-proxy-a:4180"

    oauth2-b-svc:
      loadBalancer:
        servers:
          - url: "http://oauth2-proxy-b:4181"
```

---

## 9. What This New Dynamic File Does

This file groups together the new routes introduced for identity-aware access control.

It contains:

* `/secure-a` -> `oauth2-proxy-a`
* `/oauth2/a` -> `oauth2-proxy-a` callback and auth paths
* `/secure-b` -> `oauth2-proxy-b`
* `/oauth2/b` -> `oauth2-proxy-b` callback and auth paths

Keeping these routes together makes the authentication flow easier to follow.

---

## 10. Start the Lab 2 Base First, Then Add the New Identity Components

Lab 3 extends the final working Lab 2 environment.

If the copied Lab 2 environment is currently stopped, start the existing Lab 2 base services first:

```bash
docker compose up -d traefik dozzle webgoat juice-shop app-nginx app-php postgres
```

Then confirm that the Lab 2 base is up:

```bash
docker compose ps
docker compose logs traefik --tail=50
curl -k -I https://localhost:8443/dashboard/
```

At this point, Traefik and HTTPS should already be working before any new Lab 3 route is tested.

Do **not** start the new OAuth2 Proxy containers yet, because they still contain placeholder client secrets and will not be correctly configured until the Keycloak clients are created later.

Now start only the new Lab 3 identity components:

```bash
docker compose up -d keycloak-db keycloak
```

Then confirm they are running:

```bash
docker compose ps
docker compose logs keycloak --tail=100
docker compose logs keycloak-db --tail=50
```

---

## 11. First Browser and `curl` Tests for Keycloak

Before testing Keycloak, first confirm that the Lab 2 base is still reachable through Traefik:

```bash
curl -k -I https://localhost:8443/dashboard/
```

Now test Keycloak in a browser:

```text
https://localhost:8443/keycloak/
```

The browser should show the Keycloak interface beneath that relative path.

A command-line check is also useful:

```bash
curl -k -I https://localhost:8443/keycloak/
```

The browser is important here because the later parts of the lab rely on observing redirects, cookies, and login behaviour in the browser and its developer tools.

---

## 12. What Could Go Wrong at This Stage

Common issues include:

* Traefik is not running because the Lab 2 base was not started first
* Keycloak cannot connect to its database
* wrong `KC_HTTP_RELATIVE_PATH` setting
* reverse-proxy headers not configured correctly
* wrong route or wrong Traefik network
* stale containers still using an older Compose definition

---

## 13. Exercises

1. Explain why Lab 3 extends the final Lab 2 stack rather than starting from a new blank stack.
2. Identify which existing Lab 2 files are kept unchanged.
3. Explain what the new `lab3-keycloak-routes.yml` file is for.
4. Explain why the Lab 2 base services must be started before Keycloak is tested through `https://localhost:8443/keycloak/`.
