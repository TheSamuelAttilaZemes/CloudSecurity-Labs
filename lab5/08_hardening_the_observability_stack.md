# Part 8: Hardening the Observability Stack

## 1. Overview

Observability tools are useful, but they can also become sensitive targets.

They often contain:

* application logs
* infrastructure topology clues
* performance patterns
* security-tool output
* administrative dashboards

That means the observability stack itself needs some protection.

## 2. Why This Matters

A monitoring or logging platform can reveal a great deal about the environment.

If exposed carelessly, it may help an attacker understand:

* service names and architecture
* internal dependencies
* error behaviour
* target endpoints
* operational weaknesses

## 3. What Needs Protection in This Lab

Sensitive elements in this lab include:

* Grafana dashboards
* Prometheus query interface
* Loki query interface
* Dozzle live container logs
* reverse-proxy access logs
* CrowdSec processing output

## 4. Direct Exposure vs Reverse-Proxy Publishing

Earlier in the lab, Grafana was published directly on port `3000` for simplicity.

That is useful during setup, but not the best long-term design.

A better design is to place Grafana behind Traefik and remove the direct published port.

The same idea applies to any other observability interface that does not need direct exposure.

## 5. Example Traefik-Based Publication for Grafana

One simple approach is to publish Grafana under a path such as `/grafana` or under a dedicated host if local name resolution is available.

A path-based example is easier in this lab environment.

Grafana service labels could look like this:

```yaml
    labels:
      - 'traefik.enable=true'
      - 'traefik.docker.network=frontend_net'
      - 'traefik.http.routers.grafana.rule=PathPrefix(`/grafana`)'
      - 'traefik.http.routers.grafana.entrypoints=websecure'
      - 'traefik.http.routers.grafana.tls=true'
      - 'traefik.http.services.grafana.loadbalancer.server.port=3000'
```

## 6. Why Path-Based Routing Is Suggested Here

The earlier labs already use path-based routing on `https://localhost:8443`.

That keeps the access model familiar and avoids requiring local hostname changes unless you specifically want to add them.

## 7. Remove the Direct Grafana Port Once Traefik Routing Works

After the Traefik route works, remove the direct port mapping from the Grafana service:

```yaml
    ports:
      - '3000:3000'
```

Then recreate Grafana.

This means Grafana will only be reachable through the reverse proxy route.

## 8. Similar Hardening Ideas for Prometheus and Loki

Prometheus and Loki are often published temporarily for setup and validation.

Once the setup is confirmed, the direct published ports can be removed so that both services remain internal.

That reduces unnecessary exposure of query interfaces and target metadata.

## 9. Access Control Considerations

Even in a lab, it is worth thinking about access control.

Questions to ask include:

* should all operators see all logs?
* should dashboards containing sensitive signals be restricted?
* should query interfaces remain internal only?

These questions become even more important in larger environments.

## 10. Exercises

1. Explain why Grafana should not remain permanently exposed directly on port `3000` if a reverse proxy is already available.
2. Add Traefik labels for Grafana and describe how they work.
3. Remove the direct Grafana port mapping after the Traefik route works.
4. Explain why Prometheus and Loki are often better left internal after setup validation.
