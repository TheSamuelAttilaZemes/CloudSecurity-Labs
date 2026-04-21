# Part 2: Restore the Lab 2 Base and Prepare the Environment

## 1. Overview

This part assumes that a copy of the final working Lab 2 repository exists, but that the environment may be stopped or only partly working.

The first task in Lab 4 is therefore to restore the Lab 2 base and confirm that it works before any new security services are added.

That is an important exercise in its own right because it establishes a known-good baseline before the environment is changed.

## 2. Why the Lab 2 Base Must Be Verified First

If the Lab 2 base is not running correctly, it becomes much harder to tell whether later problems are caused by:

* the original reverse-proxy environment
* the newly added security tooling
* or a mixture of both

So the correct order is:

1. restore the Lab 2 base
2. confirm that the known routes work
3. then add the Lab 4 services

## 3. Services Expected from the Final Lab 2 Base

At the start of this lab, the expected base services are:

* `traefik`
* `dozzle`
* `webgoat`
* `juice-shop`
* `app-nginx`
* `app-php`
* `postgres`

The existing Traefik dynamic files from Lab 2 should still be present, including:

* `traefik/dynamic/tls.yml`
* `traefik/dynamic/webgoat.yml`

## 4. Start the Lab 2 Base

If the environment is stopped, start the Lab 2 base services first:

```bash
docker compose up -d traefik dozzle webgoat juice-shop app-nginx app-php postgres
```

Then check the running containers:

```bash
docker compose ps
```

## 5. Verify the Base Routes with `curl`

Run these tests from the host:

```bash
curl -I http://localhost:8080/app
curl -k https://localhost:8443/dashboard/ | head
curl -k https://localhost:8443/dozzle/ | head
curl -k -L -o /dev/null -w 'WebGoat: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebGoat/
curl -k -L -o /dev/null -w 'WebWolf: code=%{http_code} url=%{url_effective}\n' https://localhost:8443/WebWolf/
curl -k -I https://localhost:8443/juice
curl -k https://localhost:8443/app | head
```

These tests should already be familiar from Lab 2.

## 6. Verify the Base Routes in a Browser

Also verify the routes in a browser.

Open these URLs:

* `https://localhost:8443/dashboard/`
* `https://localhost:8443/dozzle/`
* `https://localhost:8443/WebGoat/`
* `https://localhost:8443/WebWolf/`
* `https://localhost:8443/juice`
* `https://localhost:8443/app`

This matters because later parts of the lab will use browser developer tools to observe requests, responses, scan traffic, and blocked requests.

## 7. Check Logs Before Making Changes

A good habit before changing the environment is to inspect the current logs.

```bash
docker compose logs traefik --tail=100
docker compose logs webgoat --tail=50
docker compose logs juice-shop --tail=50
docker compose logs app-nginx --tail=50
```

This gives a baseline view of the environment before new tooling is added.

## 8. Create a Working Copy Before Editing Files

If you want to preserve a clean Lab 2 state, make a working copy of the repository before changing the files for Lab 4.

Example:

```bash
cd ..
cp -a lab2-traefik lab4-active-defense
cd lab4-active-defense
```

Then make the Lab 4 changes inside that new working copy.

## 9. New Files and Changes Introduced by Lab 4

Lab 4 will add or change the following types of files:

* Traefik static configuration changes to enable file-based access logs
* Compose changes to add CrowdSec and shared log storage
* report output directories for Trivy and ZAP
* optional files for the Coraza WAF add-on

## 10. Directory Preparation

Create a few directories that will be useful later in the lab:

```bash
mkdir -p reports/trivy
mkdir -p reports/zap
mkdir -p crowdsec
```

These are simple host-side working directories for reports and supporting files.

## 11. Exercises

1. Start the Lab 2 base and record which containers are running.
2. Verify at least four routes in both `curl` and a browser.
3. Identify which routes are open and which ones already involve redirects or special handling.
4. Explain why the environment should be verified before adding CrowdSec or other security tools.

## 12. Documentation and Further Reading

* Traefik documentation: https://doc.traefik.io/traefik/
* Dozzle documentation: https://dozzle.dev/guide/getting-started
* OWASP Juice Shop project: https://owasp.org/www-project-juice-shop/
* WebGoat project: https://owasp.org/www-project-webgoat/
