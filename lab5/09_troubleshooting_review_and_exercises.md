# Part 9: Troubleshooting, Review, and Final Exercises

## 1. Overview

This part collects the main troubleshooting steps and final review exercises for Lab 5.

## 2. Good Troubleshooting Order

When something goes wrong, use a consistent order:

1. confirm the Lab 4 base still works
2. confirm the observability containers are running
3. check Dozzle for live service logs
4. check Promtail and Loki logs if retained log ingestion is failing
5. check Prometheus targets and queries
6. check Grafana data source configuration and dashboard behaviour

## 3. Useful Commands

```bash
docker compose ps
docker compose logs dozzle --tail=100
docker compose logs promtail --tail=100
docker compose logs loki --tail=100
docker compose logs cadvisor --tail=100
docker compose logs prometheus --tail=100
docker compose logs grafana --tail=100
docker compose logs traefik --tail=100
docker compose logs crowdsec --tail=100
```

## 4. Common Problems

### 4.1 Dozzle shows no logs

Check:

* Docker socket mount exists
* Dozzle is running
* target containers are running and producing output

### 4.2 Loki shows no useful logs

Check:

* Promtail is running
* Promtail configuration path is correct
* Docker log path mount exists
* Loki data source is configured correctly in Grafana

### 4.3 Prometheus target is DOWN

Check:

* the cAdvisor container is running
* the target name in `prometheus.yml` is correct
* both services share the required Docker network

### 4.4 Grafana cannot reach Prometheus or Loki

Check:

* data source URLs use internal service names
* Grafana is attached to the internal network where Prometheus and Loki are reachable
* the backend services are running

### 4.5 Dashboards or Explore views appear empty

Check:

* whether Prometheus is receiving metrics
* whether Loki is receiving logs
* time range selection in Grafana
* query correctness
* whether the environment has generated enough activity to display interesting results

## 5. Final Review Questions

A useful review should be able to answer questions such as:

* what is the difference between live log viewing and retained log querying?
* which tool in the lab stores metrics and which tool stores logs?
* what does Dozzle reveal that Loki does not, and vice versa?
* how do logs and metrics become observability when used together?

## 6. Final Exercises

### Exercise 1: Restore and verify the application stack

Bring the base services up from a stopped state and verify that the known routes still work before relying on any observability components.

### Exercise 2: Live logging workflow

Use Dozzle to inspect at least four containers and explain what each service’s logs reveal.

### Exercise 3: Retained logging workflow

Use Grafana Explore with Loki to query logs over a time range and explain what the results show.

### Exercise 4: Metrics workflow

Use Prometheus to verify that cAdvisor is being scraped and run at least two metric queries.

### Exercise 5: Dashboard workflow

Add Prometheus and Loki as Grafana data sources, build at least two manual metrics panels, and run at least two simple log queries.

### Exercise 6: Baseline identification

Record what appears to be normal CPU, memory, and log behaviour for at least two services during ordinary activity.

### Exercise 7: Combined investigation

Use both logs and metrics to investigate one operational or security-related scenario and explain the timeline.

### Exercise 8: Hardening review

Move Grafana behind Traefik and explain why that is preferable to leaving it directly exposed once setup is complete.

## 7. Summary

Lab 5 adds a practical observability stack to the existing environment.

The main outcomes are:

* live logs are viewable through Dozzle
* retained logs are stored and queried through Loki and Promtail
* runtime metrics are exposed by cAdvisor
* metrics are stored and queried in Prometheus
* dashboards and log exploration are performed in Grafana
* logs and metrics can be used together to explain behaviour rather than only describe it

This lab also prepares useful groundwork for later work on threat detection, incident response, and resilience because all of those depend on good telemetry.
