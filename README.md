# Real-Time Poll Management

A real-time, scalable poll management system built with **Laravel 12**, **Vue 3**, **Inertia.js**, **Tailwind CSS**, **Laravel Breeze** (Authentication), and **Laravel Reverb** (WebSockets).

---

## Features

| Feature                        | Description                                                                |
| ------------------------------ | -------------------------------------------------------------------------- |
| **Admin Poll Creation**        | Authenticated poll creation with a minimum of 2 options                    |
| **Public Voting**              | Slug-based public URLs (`/polls/{slug}`) with no account required          |
| **Anti-Duplicate Voting**      | Cookie-based protection for guests + DB constraint for authenticated users |
| **User-Agent Tracking**        | Every vote logs the voter's browser for abuse auditing                     |
| **Rate Limiting**              | The vote endpoint is throttled to 30 requests/minute per IP                |
| **Live Admin Results**         | Real-time vote count updates using private WebSocket channels              |
| **Redis Caching**              | Public poll endpoints are served from a 30-second Redis cache              |
| **Policy-Based Authorization** | `PollPolicy` ensures only poll owners can access their results             |
| **Service Layer**              | `PollService` and `VoteService` encapsulate all business logic             |

---

## Tech Stack

- **Backend Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3, Inertia.js, Tailwind CSS, Vite
- **Authentication**: Laravel Breeze
- **WebSockets**: Laravel Reverb (Private Channels), Laravel Echo, Pusher JS
- **Queues & Cache**: Redis (via Laravel Queue)
- **Database**: MySQL (or SQLite)
- **Code Quality & Testing**: Pest, Larastan (PHPStan), Rector, Pint, ESLint

---

## Local Setup

### 1. Clone the Repository

```bash
git clone https://github.com/prafful-panwar/laravel-reverb-realtime-poll.git
cd laravel-reverb-realtime-poll
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials and ensure these are set:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

### 4. Generate Reverb Keys

Run this command to auto-generate and set `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` in your `.env`. It will also update your `BROADCAST_CONNECTION` to `reverb`:

```bash
php artisan reverb:install
```

> Broadcasting will not work without this step.

### 5. Create Database & User

Log into MySQL as root and run:

```sql
CREATE DATABASE IF NOT EXISTS poll_db;
CREATE USER IF NOT EXISTS 'poll_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON poll_db.* TO 'poll_user'@'localhost';
FLUSH PRIVILEGES;
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Start All Services

Boot the Web Server, Vite, Reverb WebSockets, Queue Worker, and Log Viewer in a single command:

```bash
composer dev
```

---

## Code Quality & Testing

Run the full quality suite before committing:

```bash
composer code-shield
```

This runs:

- **Rector** — automated refactoring checks
- **Pint** — Laravel code style enforcement
- **PHPStan** — static analysis (level 5)
- **Pest** — feature & unit tests (87 assertions)
- **Pest Type Coverage** — minimum 99% type coverage
- **ESLint** — Vue/JS linting

Run tests only:

```bash
composer test
```

Auto-fix code style:

```bash
composer format
```

---

## 🚀 AWS EC2 Deployment

### 1️⃣ SSH Deploy Key Setup (One-Time Server Configuration)

Using SSH Deploy Keys allows your EC2 server to securely clone a private GitHub repository without exposing your GitHub username or password. This setup is required only once per server.

**1. Generate an SSH key on the EC2 server:**

```bash
ssh-keygen -t ed25519 -C "ec2-deploy" -f ~/.ssh/deploy_key -N ""
```

This creates:

- `~/.ssh/deploy_key` (private key – keep secure)
- `~/.ssh/deploy_key.pub` (public key – safe to share)

**2. Copy the public key:**

```bash
cat ~/.ssh/deploy_key.pub
```

**3. Add the key to GitHub:**

Go to:

Repository → Settings → Deploy Keys → Add Deploy Key

- Paste the copied public key
- Enable **Read access** (recommended for production)
- Save

**4. Load the key into SSH agent:**

```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/deploy_key
```

**5. Verify SSH authentication:**

```bash
ssh -T git@github.com
```

If successful, you will see:

```
Hi <username>! You've successfully authenticated...
```

After this, the deployment script can clone the repository automatically without prompting for credentials.

---

### 2️⃣ Automated Deployment (aws-deploy.sh)

You can fully automate the deployment process on a fresh Ubuntu 22.04/24.04 instance using the included `aws-deploy.sh` script.

**1. Create the script file on your server:**

```bash
nano aws-deploy.sh
```

**2. Paste the script contents:**
Copy the exact code from [`aws-deploy.sh`](./aws-deploy.sh) in this repository and paste it into the nano editor on your server.

**3. Update necessary variables:**
Inside the nano editor, review and modify the following variables at the top of the file as needed:

- `REPO_URL` (change this to your own repository URL if you've forked the project)
- `DB_NAME`, `DB_USER`, `DB_PASS` (customize if desired)

**4. Save and exit:**
Press `CTRL+X`, then `Y`, then `Enter` to save the file.

**5. Make the script executable:**

```bash
chmod +x aws-deploy.sh
```

**6. Run the deployment:**

```bash
./aws-deploy.sh
```

---

### 3️⃣ Architecture Overview

- `Browser → Nginx → PHP-FPM → Laravel`
- `Redis (Queue) → Supervisor Worker → Reverb → WebSocket → Browser`
