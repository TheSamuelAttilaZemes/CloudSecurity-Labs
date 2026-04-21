# Part 2: Restore the Lab 4 Base and Prepare the Environment

## 1. Overview

This part restores the final working Lab 4 environment and prepares it for the new observability services in Lab 5.

That restoration step is important because the observability stack is only useful if there is a working application environment to observe.

## 2. Why the Existing Stack Must Be Running First

Dozzle, Promtail, Loki, cAdvisor, Prometheus, and Grafana all depend on the application environment already existing.

If the base services are not running, then:

* there will be few or no logs to inspect
* there will be little or no container activity to monitor
* dashboards will have no meaningful data

So the correct order is:

1. restore the working Lab 4 base
2. verify the existing routes and services
3. add the observability services

## 3. Services Expected from the Lab 4 Base

At the start of this lab, the expected base services are:

* `traefik`
* `dozzle` if it is already present from the earlier stack
* `webgoat`
* `juice-shop`
* `app-nginx`
* `app-php`
* `postgres`
* `crowdsec` if Lab 4 was completed

Depending on the exact Lab 4 state, some of these may already exist and some may need to be recreated.

## 4. Start the Base Services

If the environment is stopped, start the existing services first.

A practical starting command is:

```bash
docker compose up -d traefik dozzle webgoat juice-shop app-nginx app-php postgres crowdsec
```

If one of those services does not exist in your current Compose file, remove it from the command and continue.

Then check the running containers:

```bash
docker compose ps
```

## 5. Verify the Existing Routes with `curl`

Run these checks from the host system:

```bash
curl -I http://localhost:8080/app
curl -k https://localhost:8443/dashboard/ | head
curl -k https://localhost:8443/dozzle/ | head
curl -k -L -o /dev/null -w 'WebGoat: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebGoat/
curl -k -I https://localhost:8443/juice
curl -k https://localhost:8443/app | head
```

These tests confirm that the core services are reachable before observability components are added.

## 6. Verify the Existing Routes in a Browser

Also verify the main routes in a browser:

* `https://localhost:8443/dashboard/`
* `https://localhost:8443/dozzle/`
* `https://localhost:8443/WebGoat/`
* `https://localhost:8443/juice`
* `https://localhost:8443/app`

This matters because later parts of the lab use browser-based dashboards and browser developer tools alongside the observability stack.

## 7. Inspect Existing Logs Before Changing Anything

Before changing the stack, inspect the current logs.

```bash
docker compose logs traefik --tail=100
docker compose logs webgoat --tail=50
docker compose logs juice-shop --tail=50
docker compose logs app-nginx --tail=50
docker compose logs crowdsec --tail=50
```

This provides a baseline view of the environment before new observability services are added.

## 8. Create Working Directories

Create a few directories that will be useful during the lab:

```bash
mkdir -p monitoring/prometheus
mkdir -p monitoring/loki
mkdir -p monitoring/promtail
mkdir -p reports/grafana
```

The `monitoring/prometheus` directory will hold the Prometheus configuration file.
The `monitoring/loki` directory will hold the Loki configuration file.
The `monitoring/promtail` directory will hold the Promtail configuration file.

## 9. Decide How Grafana Will Be Accessed

Grafana can be exposed in two main ways:

* directly with a published port for initial setup and testing
* later through Traefik for a more integrated and controlled access model

In this lab, the initial setup uses a direct published port because it is easier to confirm that Grafana works before adding more routing complexity.

A later part then hardens this design.

## 10. Observability Data Sources Already Present

Before Prometheus and Grafana are added, the environment already contains useful data sources such as:

* Traefik logs
* application logs
* CrowdSec logs and decisions
* Docker container runtime state

Lab 5 builds around those existing signals rather than inventing new artificial examples.

## 11. Exercises

1. Restore the Lab 4 base and record which containers are running.
2. Verify at least four routes using both `curl` and a browser.
3. Inspect logs from at least three different services and describe what each one reveals.
4. Explain why the observability stack should only be added after the base services are working.
