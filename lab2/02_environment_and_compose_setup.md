# Part 2: Environment and Docker Compose Setup

## 1. Prerequisites

Before beginning, confirm that the following already work on the Ubuntu VM:

* Docker Engine
* Docker Compose plugin
* OpenSSL
* SSH access
* VirtualBox NAT forwarding

Useful checks:

```bash
docker --version
docker compose version
openssl version
```



## 2. VirtualBox Port Forwarding

Create these port-forwarding rules:

* host port `8080` -> guest port `8080`
* host port `8443` -> guest port `8443`

That means:

* `http://localhost:8080` on the physical host reaches Traefik HTTP in the VM
* `https://localhost:8443` on the physical host reaches Traefik HTTPS in the VM

This is important. The host-facing and guest-facing ports must match the Traefik setup used in this repository.



## 3. Create the Working Directory

Inside the VM:

```bash
mkdir -p ~/lab2-traefik
cd ~/lab2-traefik
mkdir -p traefik/dynamic
mkdir -p certs
mkdir -p app/nginx
mkdir -p app/src
mkdir -p app/php
```

##### See the files and folder structure in the lab2 repository for required app and configuration files

```tree
├── lab2
│  ├── app
│      ├── nginx
│      │   ├── default.conf
│      ├── src
│      │   ├── index.php
│      ├── php
│      │   ├── Dockerfile
│  ├──certs
│      ├── openssl-localhost.cnf
│  ├── traefik
│      ├── dynamic
│      │   ├── tls.yml
│      │   ├── webgoat.yml
│      ├── traefik.yml
├──├── docker-compose.yml
└──├──.env.example
```



## 4. Environment Variables and Secrets

Avoid hardcoding credentials directly in `docker-compose.yml` if possible.

For this lab, start by creating `.env`  based on the example environment file:

```bash
nano.env
```

Example content from `lab2/.env.example`:

```dotenv
POSTGRES_DB=labdb
POSTGRES_USER=labuser
POSTGRES_PASSWORD=change_this_for_local_lab_use
```

This is still a lab convenience, but it is better than embedding credentials directly into every Compose service definition.



## 5. Docker Compose Options Used in This Lab

### 5.1 `image`

Selects the container image to run.

Example:

```yaml
image: traefik:v3
```

### 5.2 `build`

Builds an image from a local Dockerfile.

The PHP-FPM container uses `build` because the PostgreSQL PHP extension must be installed.

### 5.3 `container_name`

Assigns a fixed name to the running container.

This is helpful in a teaching lab because `docker ps`, `docker logs`, and `docker inspect` are easier to follow.

### 5.4 `ports`

Publishes a container port to the Docker host.

In this lab, only Traefik publishes ports.

Examples:

```yaml
ports:
  - '8080:80'
  - '8443:8443'
```

That means:

* host port `8080` forwards to Traefik HTTP on port `80` inside the container
* host port `8443` forwards to Traefik HTTPS on port `8443` inside the container

### 5.5 `volumes`

Mounts files or directories into a container.

Used here for:

* Traefik config files
* TLS certificate files
* Docker socket access
* application source files
* PostgreSQL named data storage

### 5.6 `networks`

Controls which Docker networks a service joins.

This is critical because network membership controls who can reach whom.

### 5.7 `env_file`

Loads variables from an external file such as `.env`.

### 5.8 `environment`

Defines or overrides specific environment variables for a service.

### 5.9 `labels`

Metadata read by Traefik to define routers, services, and middlewares.

### 5.10 `depends_on`

Specifies container startup ordering, but does not guarantee application readiness.

### 5.11 `restart`

Controls container restart behaviour. This lab uses `unless-stopped` for convenience.



## 6. Networks Used in the Lab

Two user-defined networks are used:

* `frontend_net`
* `backend_net` with `internal: true`

### 6.1 `frontend_net`

Used for normal reverse-proxied service traffic.

Services on it:

* Traefik
* Dozzle
* WebGoat
* Juice Shop
* app-nginx

### 6.2 `backend_net`

Used for backend-only communication.

Services on it:

* app-nginx
* app-php
* postgres

Traefik is not on this network.



## 7. Review the Full Compose File

Open the file:

```bash
nano docker-compose.yml
```

When reading it, use this order:

1. networks and volumes
2. Traefik
3. Dozzle
4. WebGoat container
5. Juice Shop
6. app-nginx
7. app-php
8. postgres

That order makes the network and ingress design easier to understand.



## 8. Compose File

Use the repository version of `docker-compose.yml` exactly as provided.

Important details to notice:

* Traefik is the only service publishing host ports
* Dozzle and the app-nginx service use `traefik.docker.network=frontend_net`
* WebGoat has no Traefik labels because it is routed by file-provider configuration
* PostgreSQL publishes no host port



## 9. Exercises

1. Identify every service that publishes a host port. Why is that a good design choice here?
2. Identify every service that uses `volumes`. What is each mount for?
3. Identify every service that uses `labels`. Why do some services need labels and others do not?
4. Which services are on `backend_net`? Why is Traefik deliberately absent from that network?
5. Explain why `.env` is preferable to hardcoding the PostgreSQL password directly into several places in the Compose file.
6. Explain why WebGoat is left off the Docker-label routing model in this revised version of the lab.
