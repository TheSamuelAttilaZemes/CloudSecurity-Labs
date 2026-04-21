# Part 3: TLS and HTTPS from Scratch

## 1. Why HTTPS Is Included in the Lab

Traefik is commonly used as an HTTPS termination point.

Even in a local lab, it is useful to understand:

* how a certificate and key are created
* how Traefik is told to use them
* how HTTP is redirected to HTTPS



## 2. OpenSSL Configuration File

This lab uses `certs/openssl-localhost.cnf`.

Open it:

```bash
nano certs/openssl-localhost.cnf
```

Key section:

```ini
[alt_names]
DNS.1 = localhost
```

The subject alternative name matters because browsers validate hostnames against the SAN list.



## 3. Generate the Self-Signed Certificate Manually

Run the following inside the VM:

```bash
openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout certs/localhost.key \
  -out certs/localhost.crt \
  -config certs/openssl-localhost.cnf
```

What the main options mean:

* `-x509` creates a self-signed certificate
* `-nodes` leaves the private key unencrypted
* `-days 365` sets the validity period
* `-newkey rsa:2048` creates a new RSA key pair
* `-keyout` writes the private key
* `-out` writes the certificate
* `-config` points to the OpenSSL config file

---

## 4. Verify the Certificate

Inspect it:

```bash
openssl x509 -in certs/localhost.crt -text -noout | less
```

Check that `DNS:localhost` appears in the SAN section.



## 5. Traefik Static Configuration for HTTPS

Open `traefik/traefik.yml`.

This file defines the entrypoints:

```yaml
entryPoints:
  web:
    address: ':80'
  websecure:
    address: ':8443'
```

It also redirects HTTP to HTTPS:

```yaml
http:
  redirections:
    entryPoint:
      to: websecure
      scheme: https
      permanent: true
```

Because the HTTPS entrypoint is `:8443`, the redirect will point to `https://localhost:8443/...` when the environment is configured correctly.



## 6. Traefik Dynamic TLS Configuration

Open `traefik/dynamic/tls.yml`.

This file tells Traefik which certificate and key to present:

```yaml
tls:
  certificates:
    - certFile: /certs/localhost.crt
      keyFile: /certs/localhost.key
```

This is dynamic configuration because it describes runtime TLS material rather than startup-only items such as entrypoints or providers.



## 7. Start the Stack

If the stack is not already running:

```bash
docker compose pull
docker compose build app-php
docker compose up -d
```

Then check Traefik:

```bash
docker compose logs traefik --tail=100
```

Look for signs that Traefik loaded successfully and did not report missing certificate files.



## 8. Test HTTP to HTTPS Redirection

From the physical host:

```bash
curl -I http://localhost:8080/app
```

You should see an HTTP redirect to `https://localhost:8443/app`.



## 9. Test the HTTPS Endpoint

Because the certificate is self-signed, use `curl -k`:

```bash
curl -k -I https://localhost:8443/dashboard/
```

The browser will show a certificate warning. That is expected for a self-signed certificate unless the certificate is explicitly trusted on the host.



## 10. Exercises

1. Explain the difference between Traefik's static config and dynamic config in this lab.
2. Explain why the certificate file is placed in the dynamic config and not in the Docker labels.
3. Run the OpenSSL command and verify that the generated certificate includes `localhost` in the SAN list.
4. Break the TLS config on purpose by temporarily changing the cert filename in `tls.yml`, then restart the stack and inspect the Traefik logs. What error appears?
5. Restore the correct configuration and confirm that `http://localhost:8080/app` redirects to `https://localhost:8443/app` again.
