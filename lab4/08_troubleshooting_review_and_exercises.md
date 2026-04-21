# Part 8: Troubleshooting, Review, and Final Exercises

## 1. Overview

This part collects the main troubleshooting steps and the final review tasks for Lab 4.

## 2. Good Troubleshooting Order

When something goes wrong, use a consistent order:

1. confirm the Lab 2 base still works
2. inspect Traefik logs and access logs
3. inspect CrowdSec logs and `cscli` state
4. verify Trivy commands and image names
5. verify ZAP target URLs
6. use a browser and developer tools to compare what the client sees

## 3. Useful Commands

```bash
docker compose ps
docker compose logs traefik --tail=100
docker compose logs crowdsec --tail=100
docker compose exec traefik sh -lc 'tail -n 50 /var/log/traefik/access.log'
docker compose exec crowdsec cscli metrics
docker compose exec crowdsec cscli alerts list
docker compose exec crowdsec cscli decisions list
```

## 4. Browser-Based Checks

The browser is useful in this lab for more than one reason.

Use it to:

* confirm that the original Lab 2 routes still work
* compare the behaviour of vulnerable applications under testing
* inspect the network tab during ZAP-related exercises
* compare allowed and blocked behaviour if the WAF add-on is used

## 5. Common Problems

### 5.1 Traefik access log file missing

Check:

* `accessLog.filePath` in `traefik/traefik.yml`
* the `traefik_logs` volume mount
* whether traffic has actually been generated

### 5.2 CrowdSec running but apparently idle

Check:

* whether the access log file contains requests
* whether the log path is mounted read-only into CrowdSec
* `cscli metrics` and `cscli alerts list`

### 5.3 Trivy scan fails to inspect local images

Check:

* Docker socket mount
* image name spelling
* whether the image exists locally or needs to be pulled
* whether the cache mount path is valid on the host

### 5.4 ZAP cannot reach the target

Check:

* target URL spelling
* whether Traefik is reachable at `https://localhost:8443`
* whether `host.docker.internal` works in the local environment

## 6. Review: What Each Tool Contributes

| Tool | Main purpose in this lab |
|---|---|
| Traefik | reverse proxy and request entry point |
| CrowdSec | analyse access logs and record alerts and decisions |
| Trivy | scan images for known issues |
| ZAP | test web application behaviour and findings |
| Optional Coraza WAF | inspect and possibly block requests in-line |

## 7. Final Exercises

### Exercise 1: Restore and verify the Lab 2 base

Bring the Lab 2 base up from a stopped state and verify that the known routes work before adding Lab 4 components.

### Exercise 2: Enable and verify file-based access logs

Show that the Traefik access log file exists and that it records requests.

### Exercise 3: CrowdSec workflow

List alerts and decisions, add a manual decision, verify it, then remove it.

### Exercise 4: Compare Trivy scans

Scan at least two images from the lab and compare the types of findings returned.

### Exercise 5: GUI and baseline ZAP workflow

Browse a vulnerable target through the ZAP GUI, then run a baseline scan and compare the results.

### Exercise 6: Compare browser, log, and tool views

Pick one request sequence and compare:

* browser developer tools
* Traefik access logs
* ZAP observations

### Exercise 7: Optional WAF comparison

If the Coraza add-on is available, compare one suspicious request with WAF off and WAF on.

## 8. Summary

Lab 4 extends the Lab 2 environment with active defense and assessment tooling.

The core outcomes are:

* the base application environment has been restored and verified
* Traefik now produces file-based access logs
* CrowdSec can read those logs and track alerts and decisions
* Trivy can inspect images used by the stack
* ZAP can test the deliberately vulnerable applications through both GUI and automated paths
* an optional WAF comparison can show in-line protection concepts

This lab also prepares useful groundwork for later work on logging, monitoring, and observability.

## 9. Documentation and Further Reading

* CrowdSec documentation: https://docs.crowdsec.net/
* Trivy documentation: https://trivy.dev/docs/latest/
* ZAP documentation: https://www.zaproxy.org/docs/
* Traefik documentation: https://doc.traefik.io/traefik/
