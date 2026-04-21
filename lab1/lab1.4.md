# LAB 1.4: Container Forensics & Advanced Linux Tools
##### **Objective:**  Diagnostic workflows using standard Linux piping tools (`|`, `grep`, `sed`). Learn to debug crashes, filter massive log files, and programmatically alter configurations.

### Pre-requisites
*   Completion of previous lab parts.
*   Your `lab-vm` VM running.

---

### Part 1: The "Needle in the Haystack" (grep & pipes)

Enterprise applications generate gigabytes of logs. Finding an error by scrolling is impossible. We will use the Linux **Pipe** (`|`) to pass the output of one command into the input of another.

#### **Step 1.1: Generating Noise**
1.  Navigate to your stack: `cd ~/lab1-stack`
2.  Ensure it's running: `docker compose up -d`
3.  **Generate Traffic:**
    *   We want to fill the logs. Run this command to hit your server 20 times:
    ```bash
    for i in {1..20}; do curl localhost:9000; done
    ```
4.  **View the mess:**
    *   Run `docker compose logs`.
    *   You will see a wall of text. Finding a specific request here is hard.

#### **Step 1.2: Filtering with `grep`**
1.  **Find specific requests:**
    *   Let's find only the logs related to the "GET" method.
    *   ```bash
        docker compose logs | grep "GET"
        ```
    *   *Concept:* The pipe `|` takes the massive log output and feeds it to `grep`, which discards any line that doesn't contain "GET".
2.  **Find specific containers:**
    *   Only show logs from the Database, ignoring the Web App.
    *   ```bash
        docker compose logs | grep "database"
        ```

#### **Step 1.3: Inspecting Configuration (The Easy Way)**
In the previous labs, `docker inspect` printed a huge JSON blob. Let's extract exactly what we need without scrolling.

1.  **Find the IP Address:**
    ```bash
    docker inspect lab1-stack-webapp-1 | grep "IPAddress"
    ```
2.  **Find Environment Variables:**
    ```bash
    docker inspect lab1-stack-webapp-1 | grep -A 5 "Env"
    ```
    *   *Flag:* `-A 5` means "Show the match **A**nd 5 lines after it". This gives you context.

---

### **Part 2: Forensics (Debugging a Crash)**

We will sabotage the application again, but this time use our new tools to diagnose it like a pro.

#### **Step 2.1: Sabotage**
1.  Edit `server.py`: `nano server.py`
2.  Add a typo on line 1: `import http.servr` (remove the 'e').
3.  Rebuild: `docker compose up -d --build`
4.  Check status: `docker ps`. (It should be missing).

#### **Step 2.2: The Targeted Search**
Instead of dumping all logs, we will hunt for the Error.

1.  **Search for "Error":**
    ```bash
    docker compose logs | grep "Error"
    ```
    *   *Result:* You might see `ModuleNotFoundError`.
2.  **Context Search:**
    *   Errors often happen *after* a specific event. Let's see what happened around the error.
    *   ```bash
        docker compose logs | grep -C 3 "Module"
        ```
    *   *Flag:* `-C 3` shows **C**ontext (3 lines before and 3 lines after the match). This shows you the exact line number in the stack trace.

#### **Step 2.3: Fixing it**
1.  Fix the typo in `server.py`.
2.  Rebuild: `docker compose up -d --build`.

---

### **Part 3: programmatic Editing with `sed`**

As an Architect, you automate things. Opening `nano` to change a config file is manual work. `sed` (Stream Editor) allows you to edit files via command line scripts.

#### **Step 3.1: The Scenario**
Your manager says: "We need to move the app from Port 8000 to Port 8080 immediately across all servers."

#### **Step 3.2: Verify current state**
1.  `cat server.py | grep "PORT"`
2.  Output should be `PORT = 8000`.

#### **Step 3.3: Apply the patch**
1.  Run the replacement:
    ```bash
    sed -i 's/8000/8080/g' server.py
    ```
    *   *Syntax:* `s/find/replace/g` (Substitute "8000" with "8080" Globally).
    *   *Flag:* `-i` means "In-place" (Edit the file directly, don't just print the result).
2.  **Verify:**
    *   `cat server.py | grep "PORT"`
    *   Output is now `PORT = 8080`.

#### **Step 3.4: Update Docker Compose**
We also need to update the port mapping in `docker-compose.yml` to match.
1.  Current line: `"9000:8000"`
2.  Run `sed`:
    ```bash
    sed -i 's/9000:8000/9000:8080/g' docker-compose.yml
    ```
3.  **Apply Changes:**
    *   `docker compose up -d --build`
4.  **Test:**
    *   `curl localhost:9000`
    *   If it works, you successfully refactored the application ports without ever opening a text editor.

---

### **Part 4: Real-time Monitoring**

Combining `tail` (follow) with `grep`.

1.  **Watch for specific traffic:**
    *   Start a live feed of logs, but only show database connections.
    ```bash
    docker compose logs -f | grep "database"
    ```
    *   *Note:* The terminal will hang there, waiting for data.
2.  **Trigger it:**
    *   Open a second terminal (SSH in again).
    *   Restart the stack: `docker compose restart webapp`
3.  **Observe:**
    *   In the first terminal, you should see the logs appear instantly as the app boots and connects to the DB.

---

### **Part 5: Cleanup & Prune**

1.  **View Disk Usage:**
    *   `docker system df`
2.  **The "Nuke" Option (Prune):**
    *   `docker system prune`
    *   Type `y`. This cleans up the stopped containers from our crash tests.

---



## Exercises & Challenges

**Topic:** Forensics & Advanced Linux Tools (Grep, Sed, Pipes)

---

### **Section A: Knowledge Check**

1.  **Piping:** Explain in your own words what the `|` character does in Linux. Give a simple example.
2.  **Grep:** What does the flag `-v` do in grep? (e.g., `grep -v "Error"`). *Hint: Use the man pages or --help.*
3.  **Sed:** We used `sed -i` to edit files in place. What happens if you run `sed` without the `-i` flag?

### **Section B: Practical Challenges**

#### **Challenge 1: Log Analytics**

1.  Generate traffic to your web app (curl it 50 times).
2.  Use a pipe and `wc -l` (Word Count - Lines) to count exactly how many lines of logs exist.
    *   *Command construction:* `docker compose logs | [your command]`.
3.  Count how many times the Database successfully accepted a connection (Search for "connection authorized" or similar in the DB logs).

#### **Challenge 2: Advanced Search**

1.  Run `docker inspect` on your web container.
2.  Use `grep` to find the "MacAddress" of the container.
3.  Use `grep` to determine if the container is currently "Running" (Search for the state).

#### **Challenge 3: Mass Refactoring (Sed)**

1. Create a dummy configuration file called `app.conf`:

   ```text
   host=localhost
   port=8080
   debug=true
   host=backup-server
   ```

2. Use `sed` to replace **all** instances of "host" with "server_address".

3. Use `sed` to delete the line containing "debug". (*Hint: Look up `sed` delete command /d*).

---

### **Section C: The Forensic Investigation**

**Scenario:** A container named `payments-service` keeps crashing immediately on startup. You suspect a missing environment variable.
**Task:**
Write down the exact sequence of commands you would use to:

1.  List the dead container to get its ID.
2.  Retrieve the logs from the dead container.
3.  Filter those logs to look for the word "Fatal" or "Panic".
4.  Inspect the dead container configuration to verify the Env variables.



**End of Lab 1** , back to: **[Home](../README.md)**

------

Enda Lee 2026
