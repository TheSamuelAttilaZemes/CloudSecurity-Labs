# Part 3: Enable Traefik Access Logs and Add CrowdSec

## 1. Overview

CrowdSec needs a reliable source of request activity so that it can parse traffic and make decisions.

In this lab, the source is the Traefik access log.

The first step is therefore to configure Traefik so that access logs are written to a file on a shared volume rather than only to standard output.

After that, CrowdSec can be added and pointed at the same log file.

## 2. What CrowdSec Does

CrowdSec is a behaviour-based security engine.

At a high level, it works in stages:

1. it reads logs or HTTP events
2. it parses those events into a structured form
3. it applies scenarios to look for suspicious patterns
4. it creates alerts and decisions when a scenario matches
5. a remediation component can later act on those decisions

In this lab, the log source is Traefik.

## 3. Useful CrowdSec Terms

A few terms are worth understanding early:

* **parser**: turns raw log lines into structured fields
* **scenario**: a detection rule that looks for a pattern over time
* **collection**: a bundle of parsers, scenarios, and supporting content
* **alert**: evidence that suspicious behaviour has been detected
* **decision**: an action record such as a ban or another remediation type
* **Local API (LAPI)**: the interface used by remediation components and some `cscli` commands

## 4. Why Access Logs Need to Be Written to a File

Traefik can write access logs to standard output, but for CrowdSec it is more convenient to write them to a file that can be shared into another container.

That allows the CrowdSec engine to read the same log file directly.

This design also reinforces a useful lesson:

a log is not very useful if nothing can reliably consume it.

## 5. Update `traefik/traefik.yml`

Take the existing Lab 2 Traefik static configuration file and update it to write access logs to a file.

Use this version:

```yaml
api:
  dashboard: true

log:
  level: INFO

accessLog:
  filePath: /var/log/traefik/access.log

entryPoints:
  web:
    address: ':80'
    http:
      redirections:
        entryPoint:
          to: websecure
          scheme: https
          permanent: true
  websecure:
    address: ':8443'

providers:
  docker:
    endpoint: 'unix:///var/run/docker.sock'
    exposedByDefault: false
  file:
    directory: /etc/traefik/dynamic
    watch: true
```

This keeps the Lab 2 structure and simply adds a file path for access logs.

## 6. Update `docker-compose.yml` for the Traefik Log Volume

Add a shared volume mount to the `traefik` service so the access log directory is persisted and can be shared with CrowdSec.

Inside the `traefik` service volumes list, add:

```yaml
      - 'traefik_logs:/var/log/traefik'
```

Then add the volume definition at the bottom of the Compose file:

```yaml
volumes:
  pgdata:
  traefik_logs:
```

If other named volumes already exist, keep them and add `traefik_logs` alongside them.

## 7. Recreate Traefik

Apply the Traefik changes:

```bash
docker compose up -d --force-recreate traefik
```

Then confirm the log file is being created:

```bash
docker compose exec traefik sh -lc 'ls -l /var/log/traefik && tail -n 20 /var/log/traefik/access.log || true'
```

If the file is empty at first, generate some traffic by visiting a route in a browser or with `curl`, then check again.

## 8. Add the CrowdSec Engine to `docker-compose.yml`

Add this service definition:

```yaml
  crowdsec:
    image: crowdsecurity/crowdsec:latest
    container_name: crowdsec
    restart: unless-stopped
    environment:
      COLLECTIONS: "crowdsecurity/traefik crowdsecurity/http-cve"
    volumes:
      - 'crowdsec_db:/var/lib/crowdsec/data/'
      - 'crowdsec_config:/etc/crowdsec/'
      - 'traefik_logs:/var/log/traefik:ro'
    networks:
      - frontend_net
```

Then extend the volume section with:

```yaml
volumes:
  pgdata:
  traefik_logs:
  crowdsec_db:
  crowdsec_config:
```

## 9. What the CrowdSec Settings Mean

### `COLLECTIONS`

This tells CrowdSec which collections to install.

In this lab:

* `crowdsecurity/traefik` provides parsers and scenarios relevant to Traefik logs
* `crowdsecurity/http-cve` provides scenarios relevant to common HTTP exploit attempts

### `traefik_logs:/var/log/traefik:ro`

This mounts the same log directory that Traefik is writing to.

The `:ro` at the end means read-only.
CrowdSec should read the logs, not change them.

### `crowdsec_db` and `crowdsec_config`

These preserve CrowdSec data and configuration across container restarts.

## 10. Start CrowdSec

```bash
docker compose up -d crowdsec
```

Then check the container and logs:

```bash
docker compose ps
docker compose logs crowdsec --tail=100
```

## 11. Verify That CrowdSec Sees the Access Logs

Generate some traffic from a browser or with `curl`, then inspect the log file and CrowdSec state:

```bash
curl -k -I https://localhost:8443/dashboard/
curl -k -I https://localhost:8443/juice
docker compose exec traefik sh -lc 'tail -n 20 /var/log/traefik/access.log'
docker compose exec crowdsec cscli metrics
```

The exact output will vary, but the important thing is that CrowdSec should now show activity rather than looking completely idle.

## 12. List Installed Collections, Parsers, and Scenarios

These commands are useful for seeing what CrowdSec has installed and what it can do:

```bash
docker compose exec crowdsec cscli collections list
docker compose exec crowdsec cscli parsers list
docker compose exec crowdsec cscli scenarios list
```

This is a good point to inspect the installed content rather than treating CrowdSec as a black box.

## 13. What Scenarios Can Do

A scenario is more than a single regex match.

It can describe logic such as:

* too many requests from one source in a period of time
* repeated probing for unusual URLs
* exploit strings associated with specific HTTP attacks
* repeated failures or errors that match a suspicious pattern

A useful mental model is that scenarios look for behaviour over time, not just one line in isolation.

## 14. CrowdSec Web Console

CrowdSec also provides a web console.

The console can be used to:

* view enrolled security engines
* view alerts and decisions
* review remediation and threat information centrally
* manage some additional integrations and features

For this lab, the command-line workflow remains the main path, but the console is useful because it provides a visual view of the same security data.

Useful console pages and guides:

* Console introduction: https://docs.crowdsec.net/u/console/intro/
* Enroll an engine in the console: https://docs.crowdsec.net/u/getting_started/post_installation/console/
* Decisions management in the console: https://docs.crowdsec.net/u/console/decisions/decisions_management/

## 15. Why This Part Matters for Later Labs

This part is not only about adding CrowdSec.

It also establishes a better logging setup for Traefik.
That will be useful later when log analysis, observability, and operational troubleshooting are covered in more detail.

## 16. Exercises

1. Update the Traefik config so access logs are written to a file.
2. Verify that the access log file exists and contains requests.
3. Start CrowdSec and explain how it can read the same log file as Traefik.
4. Run `cscli collections list`, `cscli parsers list`, and `cscli scenarios list` and describe the differences.
5. Explain what an alert and a decision represent in CrowdSec.
6. Find the CrowdSec Console documentation and identify which views would be useful for this lab.

## 17. Documentation and Further Reading

* CrowdSec documentation home: https://docs.crowdsec.net/
* CrowdSec concepts: https://docs.crowdsec.net/docs/concepts
* CrowdSec `cscli` reference: https://docs.crowdsec.net/docs/cscli/
* CrowdSec Console introduction: https://docs.crowdsec.net/u/console/intro/
* Enroll an engine in CrowdSec Console: https://docs.crowdsec.net/u/getting_started/post_installation/console/
* Traefik access log documentation: https://doc.traefik.io/traefik/observability/access-logs/
