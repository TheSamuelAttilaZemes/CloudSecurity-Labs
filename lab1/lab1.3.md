# LAB 1.3: Container Management, Forensics & Operations
##### **Objective:** Master the lifecycle management of containers. Learn how to debug crashes, inspect internal states, modify running systems, and perform "Day 2" operations like backups and cleanup.

### **Pre-requisites**
*   Completion of Lab Sheet 2 (You should have the `lab1-stack` folder with `docker-compose.yml` created).
*   Your `lab-vm` VM running.

---

### **Part 1: Forensics & Diagnostics (The "Crash Loop")**

In the real world, containers don't always start perfectly. We will intentionally break our application to learn how to fix it using Docker's diagnostic tools.

#### Step 1.1: sabotage the Stack
We will modify our code to force a crash.
1.  Navigate to your project: `cd ~/lab1-stack`
2.  Edit the server code: `nano server.py`
3.  Add a typo that causes a syntax error.
    *   Change line 1: `import http.server` -> `import http.servr` (Remove the 'e').
4.  Rebuild and run:
    ```bash
    docker compose up -d --build
    ```
5.  **Observe the Failure:**
    *   Run `docker ps`. You will likely **not** see `webapp` running.
    *   Run `docker ps -a`. You will see `webapp` with status `Exited (1) ...`.
    *   *Concept:* The container tried to start, Python failed, and the container died immediately.

#### **Step 1.2: The Autopsy (Logs)**
How do we know *why* it died?
1.  **View the crash logs:**
    ```bash
    docker compose logs webapp
    ```
2.  **Analyze the Output:**
    *   You should see a Python Stack Trace:
        `ModuleNotFoundError: No module named 'http.servr'`
    *   *Lesson:* **Always check logs first.** The answer is usually right there.

#### **Step 1.3: Fixing the issue**
1.  Edit `server.py` and fix the typo (`import http.server`).
2.  Rebuild: `docker compose up -d --build`.
3.  Verify it is stable: `docker ps`. (Status should be "Up X seconds").

#### **Step 1.4: Investigating "Silent Failures" (Inspect)**
Sometimes a container is "Up" but not working right (e.g., networking issues).
1.  **Inspect Configuration:**
    *   `docker inspect lab1-stack-webapp-1`
    *   This outputs a massive JSON object. This is the source of truth.
2.  **Filtering the noise:**
    *   Let's check the IP address Docker assigned.
    *   `docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' lab1-stack-webapp-1`
3.  **Check Environment Variables:**
    *   Run `docker exec lab1-stack-webapp-1 env`
    *   Verify `DB_HOST=postgres-db`. If this was missing or wrong, your app wouldn't be able to talk to the database.

---

### **Part 2: Execution & Modification (Getting Inside)**

Sometimes logs aren't enough. You need to enter the container to test connectivity or run database queries manually.

#### **Step 2.1: The `exec` Command**
1.  **Enter the Database Container:**
    *   `docker exec -it lab1-stack-database-1 sh`
    *   *Explanation:* `-it` keeps the session interactive. `sh` is the shell program.
2.  **Manual Network Test:**
    *   Can the Database see the Web App?
    *   `ping -c 3 webapp`
    *   *Result:* You should see a successful ping. This proves the internal DNS is working.
3.  **Exit:** Type `exit`.

#### **Step 2.2: Running One-off Commands**
You don't always need to enter the shell.
1.  **Check Python Version:**
    *   `docker exec lab1-stack-webapp-1 python --version`

---

### **Part 3: Lifecycle Management (Stop, Start, Remove)**

#### **Step 3.1: Pausing and Restarting**
1.  **Stop the App:** `docker stop lab1-stack-webapp-1`
    *   *Check:* `docker ps` (App is gone).
2.  **Start it again:** `docker start lab1-stack-webapp-1`
    *   *Check:* `docker ps` (App is back).
    *   *Note:* `stop` sends a SIGTERM signal (polite shutdown). `kill` sends SIGKILL (immediate death). Use `stop` unless it's frozen.

#### **Step 3.2: Code Updates (Rebuild vs Restart)**
Scenario: You change the HTML message in `server.py`.
1.  **The Wrong Way:** `docker restart lab1-stack-webapp-1`
    *   *Why:* This just restarts the *old* compiled image.
2.  **The Right Way:** `docker compose up -d --build`
    *   *Why:* This forces Docker to re-read the Dockerfile, build a new image layer, destroy the old container, and start a fresh one.

---

### **Part 4: Transport (Import/Export)**

How do you move a container image to a server that has no internet access (Air-gapped) or share it with a colleague without using Docker Hub?

#### **Step 4.1: Exporting (Saving)**
1.  **Find your image:** `docker images`
2.  **Save to a Tarball:**
    ```bash
    docker save -o my-web-app.tar lab1-stack-webapp
    ```
    *   *Check:* `ls -lh`. You will see a large `.tar` file containing the OS and code.

#### **Step 4.2: Importing (Loading)**
1.  **Simulate a fresh start:** `docker rmi -f lab1-stack-webapp` (Force delete the image).
2.  **Load from file:**
    ```bash
    docker load -i my-web-app.tar
    ```
3.  **Verify:** `docker images`. It's back!

---

### **Part 5: Hygiene (System Pruning)**

Docker is messy. It leaves behind "dangling" images and stopped containers that consume disk space.

1.  **View Disk Usage:**
    ```bash
    docker system df
    ```
2.  **The "Nuke" Option (Prune):**
    ```bash
    docker system prune
    ```
    *   Type `y` to confirm.
    *   *Warning:* This deletes **all** stopped containers and unused networks. It does **not** delete running containers or persistent Volumes.

---



## Exercises & Challenges

**Topic:** Container Management & Operations (Exec, Lifecycle, Pruning)

---

### **Section A: Knowledge Check**

1.  **Lifecycle:** What is the difference between `docker stop` and `docker kill`? When should you use one over the other?
2.  **Persistence:** You stop a Postgres container and then start it again (`docker stop` -> `docker start`). Is the data lost? What if you run `docker rm` and then start a new one?
3.  **Exec:** Why do we use `-it` when running `docker exec -it ... sh`? What happens if you omit these flags?

### **Section B: Practical Challenges**

#### **Challenge 1: The Manual Backup**

1.  Start your Lab 2 stack (Web + DB).
2.  Use `docker exec` to enter the **Database** container.
3.  Create a file inside the container: `echo 'Important Data' > /tmp/backup.txt`.
4.  Exit the container.
5.  Use the `docker cp` command (look it up!) to copy that file from the running container to your laptop's home directory.

#### **Challenge 2: The Rogue Container**

1.  Run a container that sleeps forever: `docker run -d --name sleepy alpine sleep 1000`.
2.  Verify it is running.
3.  Rename the container to `awake` while it is running (hint: `docker rename`).
4.  Pause the container (hint: `docker pause`).
5.  Try to stop it. Does it work? Unpause it and then stop/remove it.

#### **Challenge 3: Air-Gapped Transfer**

1.  Identify the `postgres:15-alpine` image ID on your system.
2.  Save it to a file named `db_image.tar`.
3.  Delete the image from Docker (`docker rmi`).
    *   *Note: You must stop/remove any containers using it first.*
4.  Verify the image is gone (`docker images`).
5.  Restore it from the tar file.

---

### **Section C: Operational Hygiene**

**Scenario:** You run `docker system df` and notice you have 15GB of "Reclaimable" space.
**Question:**

1.  What objects usually take up this space (list 3 types)?
2.  You run `docker system prune`. A week later, the space is full again. Why? How can you automate this cleanup?



**End of part 1.3** , next: **[part 1.4](./lab1.4.md)**

------

Enda Lee 2026
