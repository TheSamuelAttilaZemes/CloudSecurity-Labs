# Part 7: Rate Limiting with Traefik Middleware

## 1. Why Middleware Matters

A reverse proxy does more than forward traffic.
It can also control, modify, or protect traffic before that traffic reaches the backend service.

This lab demonstrates one simple middleware: **RateLimit**.



## 2. Why Dozzle Is a Good Target for the Example

Dozzle is easy to test and easy to reach, so it is a useful first target for a rate-limit example.

The lab configures a Dozzle router with these labels:

```yaml
labels:
  - 'traefik.http.middlewares.dozzle-ratelimit.ratelimit.average=5'
  - 'traefik.http.middlewares.dozzle-ratelimit.ratelimit.burst=10'
  - 'traefik.http.routers.dozzle.middlewares=dozzle-ratelimit'
```



## 3. What the Rate Limit Means

* `average=5` means the steady request rate allowed is low
* `burst=10` means a short burst above that rate is allowed before the control reacts

If requests come too quickly, Traefik should begin returning `429 Too Many Requests`.



## 4. Test the Rate Limit

Run the following from inside the VM:

```bash
for i in $(seq 1 25); do
  curl -k -s -o /dev/null -w '%{http_code}\n' https://localhost:8443/dozzle/ ;
done
```

Depending on timing, some responses should become `429`.



## 5. Compare with a Route Without Rate Limiting

Now test a route that does not use the rate-limit middleware:

```bash
for i in $(seq 1 25); do
  curl -k -s -o /dev/null -w '%{http_code}\n' https://localhost:8443/app ;
done
```

This should normally continue returning successful responses.



## 6. Observe the Results in Logs

While testing, keep another terminal open with:

```bash
docker logs -f traefik
```

Also open Dozzle and watch how the traffic pattern appears there.



## 7. Exercises

1. Run the Dozzle rate-limit test and record how many responses return `429`.
2. Increase the `average` limit in the Compose labels, recreate the stack, and repeat the test. What changes?
3. Remove the Dozzle middleware attachment from the router, recreate the stack, and repeat the burst test. What changes?
4. Explain the difference between a rate-limit middleware and a strip-prefix middleware.
5. Suggest one other service in the lab where rate limiting might be useful and explain why.
