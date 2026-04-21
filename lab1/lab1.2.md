# LAB 1.2: Containerization & Orchestration
##### **Objective:** Install the Docker engine and deploy a multi-tier application (Web + Database) using Docker Compose.

### Pre-requisites
*   Completion of Lab Sheet 1.
*   Your `lab-vm` VM running and accessible via SSH.

---

### Part 1: Installing the Docker Engine

We will install Docker from the official repository, not the default Ubuntu one, to ensure we have the latest features.

#### Step 1.1: Repository Setup
Copy and paste these blocks into your SSH terminal:

1.  **Install prerequisites:**
    ```bash
    sudo apt update
    sudo apt install ca-certificates curl
    sudo install -m 0755 -d /etc/apt/keyrings
    ```
2.  **Add Docker's official GPG key:**
    ```bash
    sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    sudo chmod a+r /etc/apt/keyrings/docker.asc
    ```
3.  **Add the repository:**
    ```bash
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    sudo apt update
    ```

#### Step 1.2: Installation & Permissions
1.  **Install Docker:**
    ```bash
    sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    ```
2.  **Fix User Permissions:**
    *   By default, only `root` can run Docker. Let's add your user `student` to the docker group.
    ```bash
    sudo usermod -aG docker $USER
    ```
3.  **Apply Changes:**
    *   You **must** log out and log back in for this to work.
    *   Type `exit` in the terminal.
    *   Reconnect: `ssh -p 2222 lab-user@localhost`
4.  **Verify:**
    *   Run `docker run hello-world`
    *   If you see *"Hello from Docker!"*, you are ready.

---

### **Part 2: Docker Basics**

#### **Step 2.1: Your First Web Server**
1.  Run a simple Nginx web server:
    ```bash
    docker run -d -p 8080:80 --name web1 nginx:alpine
    ```
    *   `-d`: Detached mode (background).
    *   `-p 8080:80`: Traffic on Host Port 8080 goes to Container Port 80.
2.  **VirtualBox Networking Step:**
    *   Docker opened port 8080 on the *Linux VM*. You need to open it on *VirtualBox* too.
    *   Go to VM Settings -> Network -> Port Forwarding.
    *   Add: **Host:** `8080`, **Guest:** `8080`.
3.  **Test:** Open your laptop browser and go to `http://localhost:8080`. You should see "Welcome to nginx!".

#### **Step 2.2: Interacting with Containers**
1.  **List running containers:** `docker ps`
2.  **View Logs:** `docker logs web1` (Useful for debugging).
3.  **Stop/Remove:**
    ```bash
    docker stop web1
    docker rm web1
    ```

---

### **Part 3: Advanced Orchestration (Web + Database)**

We will now simulate a real enterprise architecture pattern: A Python Web App connecting to a Postgres Database using Docker Compose.

#### **Step 3.1: The Workspace**
1.  Create a project folder:
    ```bash
    mkdir ~/lab1-stack
    cd ~/lab1-stack
    ```
2.  Create a generic Python web server script:
    *   `nano server.py`
    *   *Paste the following code (It simulates a web app checking a database):*
    ```python
    import http.server
    import socketserver
    import os
    
    PORT = 8000
    DB_HOST = os.getenv("DB_HOST", "localhost")
    
    class Handler(http.server.SimpleHTTPRequestHandler):
        def do_GET(self):
            self.send_response(200)
            self.send_header('Content-type', 'text/html')
            self.end_headers()
            # Respond with the DB configuration
            message = f"<h1>Web App Active</h1><p>Connected to Database at: <b>{DB_HOST}</b></p>"
            self.wfile.write(bytes(message, "utf8"))
    
    with socketserver.TCPServer(("", PORT), Handler) as httpd:
        print(f"Serving on port {PORT}")
        httpd.serve_forever()
    ```

3.  Create a `Dockerfile` to package this app:
    *   `nano Dockerfile`
    ```dockerfile
    FROM python:3.12-slim
    WORKDIR /app
    COPY server.py .
    CMD ["python", "server.py"]
    ```

#### **Step 3.2: Defining the Stack (Docker Compose)**
Create the architectural blueprint.
*   `nano docker-compose.yml`

```yaml
services:
  # Service 1: The Web Application
  webapp:
    build: .
    ports:
      - "9000:8000"  # Map host 9000 -> Container 8000
    environment:
      - DB_HOST=postgres-db  # Telling the app where the DB is
    networks:
      - app-network
    depends_on:
      - database

  # Service 2: The Database
  database:
    image: postgres:15-alpine
    environment:
      - POSTGRES_PASSWORD=secretpassword
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

#### **Step 3.3: Launch and Verification**
1.  **Build and Run:**
    ```bash
    docker compose up -d --build
    ```
2.  **VirtualBox Networking Step:**
    *   Add a new Port Forwarding Rule: **Host:** `9000`, **Guest:** `9000`.
3.  **Test:**
    *   Open your browser to `http://localhost:9000`.
    *   You should see: **"Connected to Database at: postgres-db"**.
4.  **Analysis:**
    *   The Python app knew where the database was because we injected the `DB_HOST` environment variable in the compose file.
    *   Docker's internal DNS resolved the name `postgres-db` to the internal IP address of the database container.

#### **Step 3.4: Cleanup**
To stop the stack and remove the network:
```bash
docker compose down
```

---
**Deliverable Check:** Show your tutor the browser running the Python App connected to the Postgres container.



## Exercises & Challenges

**Topic:** Containerization & Orchestration (Docker, Docker Compose)

---

### **Section A: Knowledge Check**

1.  **Isolation:** If you delete a file inside a running Docker container, is it deleted from your laptop's file system? Why or why not?
2.  **Ports:** In the command `docker run -p 9000:80 nginx`, which number is the port on your laptop, and which is the port inside the container?
3.  **DNS:** In our Docker Compose stack, the Python app connected to the host `postgres-db`. This hostname does not exist on the internet. How did the Python app know how to find the database?

### **Section B: Practical Challenges**

#### **Challenge 1: The Custom Message**

1.  Modify the `server.py` script from Lab 2.
2.  Change the HTML output to include your own name (e.g., "Maintained by: [Your Name]").
3.  Update the running stack to show this change.
    *   *Constraint:* Do not delete the containers manually. Use a single `docker compose` command to rebuild and restart.

#### **Challenge 2: Adding a Cache (Redis)**

1.  Edit your `docker-compose.yml`.
2.  Add a **third service** named `cache` using the image `redis:alpine`.
3.  Ensure it is on the same network (`app-network`).
4.  Launch the stack.
5.  Verify the Redis container is running using `docker ps`.

#### **Challenge 3: Environment Variables**

1.  Currently, the database password (`secretpassword`) is hardcoded in the YAML file. This is bad practice.
2.  Create a file named `.env` in the same folder.
3.  Move the password into that file (e.g., `DB_PASS=supersecure`).
4.  Update `docker-compose.yml` to use the variable (syntax: `${DB_PASS}`).
5.  Re-launch the stack and verify it still works.

---

### **Section C: Debugging Scenario**

**Scenario:** You run `docker compose up` and see an error: `Bind for 0.0.0.0:9000 failed: port is already allocated`.
**Task:**

1.  Explain what this error means.
2.  Provide the command to find which process is using port 9000.
3.  Provide the fix (either killing the old process or changing the port in compose).



**End of part 1.2** , next: **[part 1.3](./lab1.3.md)**

------

Enda Lee 2026
