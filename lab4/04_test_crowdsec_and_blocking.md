# Part 4: Test CrowdSec Decisions, Remediation, and Blocking Workflow

## 1. Overview

This part focuses on how CrowdSec records and manages alerts and decisions, and how those decisions can later be enforced by a remediation component.

The first half of this part stays with the CrowdSec engine itself:

* inspect alerts
* inspect decisions
* add and remove decisions manually
* generate traffic and observe what changes

The second half introduces the remediation concept and the Traefik plugin path.

## 2. Alerts vs Decisions

These two terms are related but different.

* An **alert** means CrowdSec has detected suspicious behaviour.
* A **decision** is the action record created from that detection, such as a ban.

This distinction matters because not every investigation starts with blocking. Sometimes the first useful step is simply to observe what the engine has detected.

## 3. View Current Decisions

Run:

```bash
docker compose exec crowdsec cscli decisions list
```

Initially, the list may be empty.

That is normal if no scenarios have fired yet and no manual decisions have been created.

## 4. View Alerts

Run:

```bash
docker compose exec crowdsec cscli alerts list
```

This lets you distinguish between:

* suspicious activity that has been recognised
* and formal decisions that have been created from it

## 5. Decision Types and Scope

CrowdSec decisions can vary by type and by scope.

Examples of decision types include:

* `ban`
* `captcha`

Examples of scope include:

* `ip`
* `range`
* `session`

In this lab, IP-based manual decisions are the simplest starting point.

## 6. Add a Manual Decision

A manual decision is a good way to understand the workflow without needing to trigger a real detection scenario first.

Example:

```bash
docker compose exec crowdsec cscli decisions add --ip 1.2.3.4 --duration 1h --reason "Manual Lab Test"
```

Then inspect the list again:

```bash
docker compose exec crowdsec cscli decisions list
```

## 7. Filter Decision Output

The `cscli decisions list` command supports useful filters.

Examples:

```bash
docker compose exec crowdsec cscli decisions list --since 4h
docker compose exec crowdsec cscli decisions list --scope ip
docker compose exec crowdsec cscli decisions list --type ban
```

These options are useful when the data set becomes larger.

## 8. Remove a Decision

To delete the test decision:

```bash
docker compose exec crowdsec cscli decisions delete --ip 1.2.3.4
```

Then check the list again:

```bash
docker compose exec crowdsec cscli decisions list
```

## 9. Generate Real Traffic and Review Metrics Again

Generate a burst of normal traffic:

```bash
for i in $(seq 1 20); do
  curl -k -s -o /dev/null https://localhost:8443/juice ;
done
```

Then check the CrowdSec metrics and alerts again:

```bash
docker compose exec crowdsec cscli metrics
docker compose exec crowdsec cscli alerts list
```

The point here is not necessarily to trigger a ban immediately, but to observe that CrowdSec is tracking traffic and applying parsers and scenarios.

## 10. Browser-Based Observation

Use a browser and developer tools while generating requests.

For example:

* open Juice Shop in a browser
* refresh repeatedly
* inspect the browser network tab
* compare what the browser sees with what appears in the access log and CrowdSec metrics

This reinforces the connection between client activity, access logs, and CrowdSec analysis.

## 11. What a Remediation Component Does

The CrowdSec engine by itself is a detection engine.

It can:

* parse logs
* detect suspicious behaviour
* create alerts
* create decisions

But it does not enforce blocking on its own.

A **remediation component** is what acts on the decisions.

In a Traefik-based environment, the natural remediation component is the Traefik CrowdSec bouncer plugin.

## 12. Why the Traefik Plugin Matters

For a Traefik environment, the plugin approach is the current supported path rather than the older deprecated separate Traefik bouncer container.

The plugin can ask the CrowdSec Local API whether the incoming source has an active decision.

That means the overall flow becomes:

1. CrowdSec analyses logs and creates decisions
2. the Traefik remediation plugin checks those decisions during request handling
3. matching traffic can be denied before it reaches the upstream application

## 13. Example Traefik Plugin Middleware Configuration

The exact configuration can evolve, so always check the official plugin documentation before final use.

A conceptual example looks like this:

```yaml
http:
  middlewares:
    crowdsec-bouncer:
      plugin:
        bouncer:
          enabled: true
          crowdsecMode: live
          crowdsecLapiScheme: http
          crowdsecLapiHost: crowdsec:8080
          crowdsecLapiKey: CHANGE_ME_WITH_REAL_BOUNCER_KEY
```

The important ideas are:

* the middleware must be enabled
* it must know how to reach the CrowdSec Local API
* it must authenticate with a valid bouncer key

## 14. Generate a Bouncer Key

A bouncer key is how the remediation component is authorised to query the CrowdSec Local API.

Generate one with:

```bash
docker compose exec crowdsec cscli bouncers add traefik-bouncer
```

Copy the key when it is displayed and place it into the Traefik plugin configuration.

## 15. Where the CrowdSec Console Fits

The web console is useful here because it gives a visual way to inspect:

* enrolled engines
* alerts
* decisions
* remediation behaviour

So the same workflow can be viewed in two ways:

* command-line with `cscli`
* browser-based console view

## 16. Exercises

1. List the current CrowdSec decisions before adding any test decision.
2. Add a manual decision for a test IP and verify that it appears.
3. Delete the decision and verify that it disappears.
4. Generate traffic and compare CrowdSec metrics with the Traefik access log.
5. Explain the difference between the CrowdSec engine and a remediation component.
6. Explain what information the Traefik plugin needs in order to enforce CrowdSec decisions.

## 17. Documentation and Further Reading

* CrowdSec `cscli decisions` reference: https://docs.crowdsec.net/docs/cscli/cscli_decisions
* `cscli decisions list`: https://docs.crowdsec.net/docs/cscli/cscli_decisions_list
* CrowdSec remediation components overview: https://docs.crowdsec.net/u/bouncers/intro/
* CrowdSec Docker installation path: https://docs.crowdsec.net/u/getting_started/installation/docker
* CrowdSec Traefik quickstart: https://docs.crowdsec.net/docs/appsec/quickstart/traefik/
* CrowdSec Traefik plugin page: https://plugins.traefik.io/plugins/6335346ca4caa9ddeffda116/crowdsec-bouncer-traefik-plugin
* CrowdSec Console introduction: https://docs.crowdsec.net/u/console/intro/
