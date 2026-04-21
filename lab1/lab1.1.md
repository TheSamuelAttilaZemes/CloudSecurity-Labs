# LAB 1.1: Fundamentals
##### **Objective:** Set up a virtualized Linux server environment and master the essential command-line skills required for an Enterprise lab-user.

### Pre-requisites
*   A laptop or PC (Windows, Mac, or Linux).

*   **Oracle VirtualBox** (Version 7.0+) installed.

*   **Ubuntu Server 24.04 LTS ISO** file downloaded.
    *   See https://ubuntu.com/download/server

    

### If using ARM64/ Apple Silicon

To setup on an Apple Macbook you will need to download the ARM64 version of the Ubuntu Server ISO.

Virtualbox is not available for Apple Silicon, but here are some free alternatives:

* VMWare Fusion: https://blogs.vmware.com/cloud-foundation/2024/11/11/vmware-fusion-and-workstation-are-now-free-for-all-users/
* UTM: https://mac.getutm.app/
* [Parallels](https://www.parallels.com/products/desktop/) is a good option but there  no free version available



### Part 1: The Virtual Data Center

In this section, you will provision a "Bare Metal" server. In the cloud (AWS/Azure), this happens automatically, but an lab-user must understand what is happening under the hood.

#### Step 1.1: VM Provisioning
1.  Open **VirtualBox** and click **New**.
2.  **Configuration:**
    *   **Name:** `lab-vm`
    *   **ISO Image:** Select your downloaded Ubuntu Server ISO.
    *   **Type:** Linux / Ubuntu (64-bit).
    *   **Hardware:**
        *   **Base Memory (RAM):** 4096 MB (4GB) (or 8192MB / 8GB if available).
        *   **Processors:** 2 - 4 CPUs (If your laptop/PC allows).
    *   **Hard Disk:** Create a Virtual Hard Disk (VDI), 32GB, Dynamically Allocated.

#### Step 1.2: Network Config (Port Forwarding)
By default, your VM is isolated. We need to punch a hole in the virtual firewall to let your laptop talk to it.

1.  Select your VM -> **Settings** -> **Network**.
2.  **Adapter 1:** Ensure it is attached to **NAT**.
3.  Click **Advanced** -> **Port Forwarding**.
4.  Add a new Rule (Click the green `+`):
    *   **Name:** `SSH`
    *   **Protocol:** `TCP`
    *   **Host Port:** `2222` (This is the port on your physical laptop).
    *   **Guest Port:** `22` (This is the standard SSH port inside the Linux VM).
5.  Click **OK** twice.

### **Step 1.3: OS Installation**
1.  Start the VM.
2.  Follow the installer prompts using the arrow keys and Enter.
    *   **Language:** English.
    *   **Network Connections:** Accept defaults (DHCP).
    *   **Profile Setup:**
        *   **Your name:** `Lab User`
        *   **Server name:** `lab-vm`
        *   **Username:** `lab-user`
        *   **Password:** (Choose something you will remember, e.g., `password`).
    *   **SSH Setup (Critical):** Select **[X] Install OpenSSH server**.
    *   **Featured Snaps:** Do **NOT** select anything (no Docker/MicroK8s yet). We will install them manually to learn how they work.
3.  When finished, select **Reboot Now**. (If it asks to remove installation medium, press Enter).

---

## **Part 2: The Headless Interface (SSH)**

Enterprise servers rarely have a graphical user interface (GUI). You manage them remotely via Secure Shell (SSH).

### **Step 2.1: Connecting**
1. Open your local computer's terminal:
   *   **Windows:** PowerShell or Command Prompt.
   *   **Mac/Linux:** Terminal.
2. Run the connection command:
   ```bash
   ssh -p 2222 lab-user@localhost
   ```
   *   *-p 2222 targets the port we forwarded earlier.*
3. Type `yes` to accept the security fingerprint.
4. Enter the password you created during installation.
5. **Success:** You should see a prompt like `lab-user@lab-vm:~$`.

#### If you cannot connect

1. Verify that the `ssh`  service is running on your server:

   ```bash
   sudo systemctl status ssh
   ```

2. If ssh is missing, install and enable the service:

   ```bash
   sudo apt update
   sudo apt install openssh-server
   sudo systemctl enable --now ssh
   ```

3. Try connecting again.

if the service fails to start try `sudo /usr/bin/ssh-keygen -A` and try again.

---

## **Part 3: Linux Survival Skills**

### **Step 3.1: System Hygiene (sudo & apt)**
Linux separates "User" privileges from "Root" (Admin) privileges. Commands that change the system require `sudo`.

1.  **Update the Catalog:**
    ```bash
    sudo apt update
    ```
    *   *Note:* This downloads the *list* of new software, it doesn't install it.
2.  **Upgrade the System:**
    ```bash
    sudo apt dist-upgrade -y
    ```
    *   *Note:* This installs the actual security patches.
3.  **Clean Up:**
    ```bash
    sudo apt clean
    sudo apt autoremove
    ```

### **Step 3.2: File Management**
1.  **Navigation:**
    *   **`pwd`** (Print Working Directory): Check where you are.
    *   **`ls -la`** (List All): See files, including hidden ones (starting with `.`).
2.  **Manipulation:**
    *   Create a workspace: `mkdir lab1`
    *   Enter it: `cd lab1`
    *   Create an empty file: `touch lab_notes.txt`
3.  **Editing (nano):**
    *   Open the file: `nano lab_notes.txt`
    *   Type: *"Enterprise lab-userure is about trade-offs."*
    *   **Save:** Press `Ctrl + O`, then Enter.
    *   **Exit:** Press `Ctrl + X`.
4.  **Moving & Copying:**
    *   Copy the file: `cp lab_notes.txt backup_notes.txt`
    *   Rename the file: `mv lab_notes.txt main_notes.txt`

### **Step 3.3: Permissions & Services**
1.  **Check Permissions:**
    *   Run **`ls -l`**. Look at the left column (e.g., `-rw-r--r--`).
    *   `rw-` means the owner can Read/Write. `r--` means others can only Read.
2.  **Managing Services (Systemd):**
    *   Check if SSH is running: **`sudo systemctl status ssh`**
    *   *Try stopping it:* **`sudo systemctl stop ssh`**
    *   **Result:** Your connection freezes! You just locked yourself out.
    *   **Fix:** Restart the VM via the VirtualBox window ("Machine" -> "Reset"). Reconnect via SSH.

### **Step 3.4: Environment Variables**
1.  Set a variable: `export APP_ENV=production`
2.  Check it: `echo $APP_ENV`
3.  **Persistence:**
    *   If you reboot, that variable disappears.
    *   To save it forever, add it to your profile:
    ```bash
    echo 'export APP_AUTHOR="Your Name"' >> ~/.bashrc
    source ~/.bashrc
    ```

---



## Exercises & Challenges

**Topic:** Infrastructure Fundamentals (VirtualBox, SSH, Linux Basics)

These exercises are designed to test your understanding of the core concepts covered in Lab 1. Complete them after finishing the main lab activities.

---

### Section A: Knowledge Check

1.  **Port Forwarding:** Explain why we mapped Host Port `2222` to Guest Port `22`. What would happen if we tried to map Host Port `22` directly? (Hint: Does your laptop already use port 22?)
2.  **Sudo:** What is the difference between running `apt update` and `sudo apt update`? Why does Linux default to a non-root user?
3.  **Persistence:** You set an environment variable `APP_ENV=dev`. You log out and log back in, and it is gone. Why? How exactly does the `.bashrc` file solve this?

### Section B: Practical Challenges

#### Challenge 1: The Hidden Directory

1.  Create a directory named `.secret_project` inside your home folder.
2.  Create a file inside it called `plans.txt`.
3.  Run the standard `ls` command. Can you see the directory?
4.  Run the command required to reveal it.

#### Challenge 2: The Service Manager

1.  We worked with SSH (`sudo systemctl status ssh`).
2.  There is a time-synchronization service called `systemd-timesyncd`.
3.  Check its status. Is it active?
4.  Stop it. Check status again.
5.  Start it back up.

#### Challenge 3: Permissions Hardening

1.  Create a file named `top_secret.txt`.
2.  Run `ls -l` and note the permissions (e.g., `-rw-r--r--`).
3.  Use the `chmod` command to remove "Read" permissions for "Others" and "Group". Only the "Owner" (you) should be able to read it.
    *   *Hint:* You might need to look up `chmod 600` or `chmod u+rw`.
4.  Verify the change using `ls -l`.



### Section C: Troubleshooting Scenario

**Scenario:** You reboot your VM. You try to SSH in using `ssh -p 2222 lab-user@localhost`, but it hangs and times out.
**Question:** List three possible reasons why this connection might be failing.



**End of part 1.1** Ensure your VM is updated and you can SSH into it reliably before starting **[part 1.2](./lab1.2.md)**

------

Enda Lee 2026