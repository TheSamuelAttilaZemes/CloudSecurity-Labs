# Part 4: Reverse Proxy Routing and Route Testing

## 1. Why Route Testing Matters

A reverse proxy configuration should not be trusted just because the YAML looks correct.

Routes should be tested explicitly.

This lab uses several different path prefixes:

* `/dashboard`
* `/dozzle`
* `/WebGoat`
* `/WebWolf`
* `/juice`
* `/app`

Not every route behaves in exactly the same way, and that is part of the lesson.
Some services return content immediately.
Some redirect before returning the final page.
Some are routed using Docker labels, while others are routed using a file-based dynamic configuration.



## 2. How Traefik Routing Works in This Lab

This lab uses more than one Traefik routing method.

That is not a mistake.
It is actually a useful teaching point.

Traefik can load dynamic configuration from different providers, including:

* the **Docker provider**
* the **file provider**

In this lab, both are used on purpose.

That means the lab does not just teach how to make Traefik work. It also shows that there is more than one way to define routes, and that different approaches may be clearer or more reliable for different services.



## 3. Static vs Dynamic Configuration

### 3.1 Static configuration

Static configuration is what Traefik needs when it starts.

Examples in this lab:

* entrypoints
* provider configuration
* log settings
* dashboard/API enablement

In this lab, the static configuration is stored in:

```text
traefik/traefik.yml
```

This file tells Traefik things like:

* listen on HTTP port 80
* listen on HTTPS port 8443
* redirect HTTP to HTTPS
* read dynamic config from Docker
* read dynamic config from files in `traefik/dynamic`

### 3.2 Dynamic configuration

Dynamic configuration describes runtime routing behaviour.

Examples:

* routers
* services
* middlewares
* TLS certificates

In this lab, dynamic configuration comes from two places:

* Docker labels in `docker-compose.yml`
* YAML files in `traefik/dynamic/`



## 4. The Core Traefik Objects

Three objects matter most for HTTP routing.

### 4.1 Router

A router decides **which requests match**.

Examples:

* requests whose path starts with `/dozzle`
* requests whose path starts with `/juice`
* requests whose path starts with `/WebGoat`
* requests whose path starts with `/WebWolf`
* requests whose path starts with `/app`

A router does not serve the response itself. It only decides where traffic should go.

### 4.2 Service

A service tells Traefik **which backend server** should receive the request.

Examples in this lab:

* Dozzle on port `8080`
* Juice Shop on port `3000`
* WebGoat on port `8080`
* WebWolf on port `9090`
* the Nginx demo application on port `80`

### 4.3 Middleware

A middleware changes or controls the request before it reaches the backend.

Examples in this lab:

* `StripPrefix` for the demo application
* `RateLimit` for Dozzle
* `RedirectRegex` for redirecting `/WebGoat` to `/WebGoat/login` if you choose to enable that improvement



## 5. Routing Method 1: Docker Labels

For most services in this lab, Traefik routes are defined with Docker labels.

This means the routing instructions live beside the service definition in `docker-compose.yml`.

A typical example is Dozzle:

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.docker.network=frontend_net"
  - "traefik.http.routers.dozzle.rule=PathPrefix(`/dozzle`)"
  - "traefik.http.routers.dozzle.entrypoints=websecure"
  - "traefik.http.routers.dozzle.tls=true"
  - "traefik.http.routers.dozzle.middlewares=dozzle-ratelimit"
  - "traefik.http.services.dozzle.loadbalancer.server.port=8080"
```

### 5.1 Why labels are useful

Labels are good when:

* the route belongs clearly to one container
* the configuration is simple
* keeping routing next to the service improves readability

That makes labels a strong fit for:

* Dozzle
* Juice Shop
* the demo application

### 5.2 Why `traefik.docker.network` matters

Traefik must know which Docker network to use when talking to a backend container.

If a service is attached to more than one network, or if network selection is ambiguous, Traefik may fail to route correctly unless the intended network is specified.

That is why this lab uses:

```yaml
- "traefik.docker.network=frontend_net"
```

This tells Traefik to reach the service on the frontend-facing network.



## 6. Routing Method 2: File Provider

WebGoat and WebWolf are routed using the file provider instead of Docker labels.

The relevant file is:

```text
traefik/dynamic/webgoat.yml
```

A simplified version looks like this:

```yaml
http:
  routers:
    webgoat:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/WebGoat`)"
      service: webgoat-svc
      tls: {}

    webwolf:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/WebWolf`)"
      service: webwolf-svc
      tls: {}

  services:
    webgoat-svc:
      loadBalancer:
        servers:
          - url: "http://webgoat:8080"

    webwolf-svc:
      loadBalancer:
        servers:
          - url: "http://webgoat:9090"
```

### 6.1 Why use the file provider here

This lab uses the file provider for WebGoat/WebWolf for two reasons:

* it demonstrates a second Traefik routing method
* it gives explicit, readable control over two related routes that both point to the same container on different ports

### 6.2 When file-based routing is useful

The file provider is often useful when:

* a route is easier to understand as standalone YAML
* several related routers/services belong together
* you want to separate application deployment from proxy-routing logic
* Docker-label discovery is awkward or unclear for a specific service



## 7. Why WebGoat and WebWolf Need Special Handling

The WebGoat container exposes two applications:

* WebGoat at `/WebGoat` on port `8080`
* WebWolf at `/WebWolf` on port `9090`

That means one container is serving two different web apps on two different ports.

This is a good example of why reverse-proxy design is not always “one container equals one route”.

It also shows why testing matters:

* the backend application may work perfectly inside the container
* but the route may still fail if the proxy config does not match the backend structure



## 8. Exact Paths, Prefix Paths, and Redirects

Traefik route rules can be very broad or very specific.

### 8.1 `Path(...)`

`Path(...)` matches one exact path only.

Example:

```yaml
rule: "Path(`/WebGoat`)"
```

This matches `/WebGoat` but not `/WebGoat/login`.

### 8.2 `PathPrefix(...)`

`PathPrefix(...)` matches a whole subtree.

Example:

```yaml
rule: "PathPrefix(`/WebGoat`)"
```

This matches:

* `/WebGoat`
* `/WebGoat/`
* `/WebGoat/login`
* `/WebGoat/css/main.css`

That usually makes it the better choice for applications that serve many nested resources under a common base path.

### 8.3 Redirecting `/WebGoat` to `/WebGoat/login`

Some applications behave better if the user lands directly on the login page.

This can be done with a separate exact-match router and a redirect middleware.

Example:

```yaml
http:
  routers:
    webgoat-redirect:
      entryPoints:
        - websecure
      rule: "Path(`/WebGoat`)"
      middlewares:
        - webgoat-login-redirect
      service: webgoat-svc
      tls: {}

    webgoat:
      entryPoints:
        - websecure
      rule: "PathPrefix(`/WebGoat/`)"
      service: webgoat-svc
      tls: {}

  middlewares:
    webgoat-login-redirect:
      redirectRegex:
        regex: "^https://localhost:8443/WebGoat$"
        replacement: "https://localhost:8443/WebGoat/login"
        permanent: false
```

This works because:

* the exact-match router catches only `/WebGoat`
* the prefix router handles everything under `/WebGoat/`
* the redirect avoids leaving the user at an awkward landing path



## 9. Request Flow Examples

### 9.1 Dozzle

1. Browser requests `https://localhost:8443/dozzle/`
2. Traefik matches the `/dozzle` router from Docker labels
3. Traefik applies the rate-limit middleware
4. Traefik forwards to the Dozzle service on port `8080`

### 9.2 Demo application

1. Browser requests `https://localhost:8443/app`
2. Traefik matches the `/app` router from Docker labels
3. Traefik applies `StripPrefix`
4. Traefik forwards to the Nginx app service on port `80`
5. Nginx forwards PHP execution to PHP-FPM
6. PHP-FPM connects to PostgreSQL on the backend network

### 9.3 WebGoat

1. Browser requests `https://localhost:8443/WebGoat/`
2. Traefik matches the `/WebGoat` file-provider router
3. Traefik forwards to `http://webgoat:8080`
4. WebGoat returns redirects or the login page depending on the path

### 9.4 WebWolf

1. Browser requests `https://localhost:8443/WebWolf/`
2. Traefik matches the `/WebWolf` file-provider router
3. Traefik forwards to `http://webgoat:9090`
4. WebWolf returns redirects or the login page depending on the path



## 10. Dashboard Route

The dashboard route uses the special Traefik service `api@internal`.

Relevant labels:

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.dashboard.rule=PathPrefix(`/dashboard`) || PathPrefix(`/api`)"
  - "traefik.http.routers.dashboard.entrypoints=websecure"
  - "traefik.http.routers.dashboard.tls=true"
  - "traefik.http.routers.dashboard.service=api@internal"
```



## 11. Dozzle Route

Dozzle is configured with a base path:

```yaml
command:
  - "--base=/dozzle"
```

This allows it to operate correctly behind `/dozzle/`.



## 12. WebGoat and WebWolf Routes

WebGoat and WebWolf are routed by the file provider in `traefik/dynamic/webgoat.yml`.

This file defines:

* a router for `/WebGoat` to the backend service on port `8080`
* a router for `/WebWolf` to the backend service on port `9090`

This is a useful example of file-provider routing in addition to Docker-label routing.



## 13. Juice Shop Route

Juice Shop is configured with:

```yaml
environment:
  BASE_PATH: /juice
```

That tells Juice Shop it is being served from a subpath rather than from `/`.



## 14. Demo App Route

The demo app route uses a `StripPrefix` middleware because the application itself expects to live at `/`.

Relevant labels:

```yaml
labels:
  - "traefik.http.routers.app.rule=PathPrefix(`/app`)"
  - "traefik.http.routers.app.middlewares=app-strip"
  - "traefik.http.middlewares.app-strip.stripprefix.prefixes=/app"
```

That means a browser request for `/app` is forwarded to the backend as `/`.



## 15. Test Routes from the Host

Use the following tests from the physical host.

Not every application behaves the same way when tested from the command line.

* the dashboard and Dozzle are easy to test by fetching the first few lines of HTML
* WebGoat and WebWolf perform redirects before reaching the login page, so it is better to follow redirects and print the final status and URL

Use:

```bash
curl -I http://localhost:8080/app
curl -k https://localhost:8443/dashboard/ | head
curl -k https://localhost:8443/dozzle/ | head
curl -k -L -o /dev/null -w 'WebGoat: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebGoat/
curl -k -L -o /dev/null -w 'WebWolf: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebWolf/
curl -k -I https://localhost:8443/juice
curl -k https://localhost:8443/app | head
```

Expected behaviour:

* `/app` should redirect from HTTP to HTTPS
* `/dashboard/` should return Traefik dashboard HTML
* `/dozzle/` should return Dozzle HTML
* `/WebGoat/` should end at `/WebGoat/login` with HTTP 200
* `/WebWolf/` should end at `/WebWolf/login` with HTTP 200
* `/juice` should return HTTP 200
* `/app` should return the demo application HTML



## 16. Test Traefik Live Configuration

Because the dashboard API is enabled, you can query Traefik’s live view of routers and services.

Examples:

```bash
curl -k https://localhost:8443/api/http/routers | jq
curl -k https://localhost:8443/api/http/services | jq
```

This is especially useful when checking whether a file-provider route such as WebGoat has actually been loaded.



## 17. How to Debug Traefik Routing

This lab showed that route debugging is not just about reading YAML.

A better workflow is:

1. check container state with `docker compose ps`
2. check Traefik logs with `docker compose logs traefik`
3. test the route with `curl`
4. inspect Traefik’s live config with:

```bash
curl -k https://localhost:8443/api/http/routers | jq
curl -k https://localhost:8443/api/http/services | jq
```

5. test the backend directly from inside the container

For WebGoat, these internal checks are especially useful:

```bash
docker compose exec webgoat sh -lc 'wget -S -O- http://127.0.0.1:8080/WebGoat 2>&1 | head -40'
docker compose exec webgoat sh -lc 'wget -S -O- http://127.0.0.1:9090/WebWolf 2>&1 | head -40'
```

These commands help separate a backend application problem from a reverse-proxy routing problem.



## 18. Exercises

1. Use the full route test sequence and record the final result for every service.
2. Explain the difference between Docker-label routing and file-provider routing in this lab.
3. Temporarily remove the `BASE_PATH` environment variable for Juice Shop, recreate the container, and observe what breaks.
4. Temporarily remove the `app-strip` middleware from the demo app router and explain what changes when you visit `/app`.
5. Explain why Dozzle uses a base-path setting while the demo app uses `StripPrefix` instead.
6. Explain why WebGoat and WebWolf are better tested with `curl -L` than with `curl -I` in this lab.
7. Describe one situation where the file provider might be easier to manage than Docker labels.
