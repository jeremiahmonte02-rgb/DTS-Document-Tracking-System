# Document Tracking System (DTS)
**Live demo:** [https://distributors-duty-walt-electricity.trycloudflare.com](https://distributors-duty-walt-electricity.trycloudflare.com)

> This is a temporary Cloudflare Quick Tunnel link, active only while the
> team is actively running the project. If it doesn't load, it has likely
> expired — please contact the team at **itb11dts@gmail.com** for access.


> This link is **temporary** and may not always be available (e.g. free-tier hosting spin-down, expired tunnel, or the service being taken offline after submission/grading). If it fails to load, please contact the team at **itb11dts@gmail.com**.

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13.8, Laravel Reverb 1.11 (websockets, Pusher protocol), Laravel Tinker 3.0, Maatwebsite Excel 4.0
- **Frontend (web):** Server-rendered Blade templates, Bootstrap 5.3 + Bootstrap Icons, Chart.js, QRCode.js, html5-qrcode, vanilla JS modules — built with Vite 8 (`laravel-vite-plugin` 3.1) and Tailwind 4
- **Database:** MySQL 8.0 (SQLite supported as a fallback/dev option)
- **Other infra:** Redis config available (optional — not required by default), queue/cache/session drivers default to `database`, Apache reverse proxy for websocket traffic (`/app/*`)
- **Mobile:** None currently in this repo

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ and npm
- MySQL 8.0 (or SQLite for lightweight local dev)
- Docker & Docker Compose (for the containerized setup)

## 1. Clone the Repository

```bash
git clone <repo-url>
cd document-tracker
```

This is a single repository — the Laravel application lives under `src/`, with Docker configuration at the project root.

## Quick Start (Docker)

```bash
docker compose up -d --build
```

This brings up:

| Service     | Purpose                          | Port (host) |
|-------------|-----------------------------------|--------------|
| `app`       | Laravel app (Apache)              | 8082 → 80    |
| `db`        | MySQL 8.0                         | 3307 → 3306  |
| `phpmyadmin`| DB admin UI                       | 8081         |
| `reverb`    | Websocket server (Reverb)         | 8080         |
| `scheduler` | Runs Laravel's scheduler every 60s| —            |

Once it's up, visit **http://localhost:8082**.

> This covers backend + database + websockets + scheduler in one command — no separate manual steps needed if you're using Docker.

## 2. Database Setup (manual, if not using Docker)

```bash
php artisan migrate
php artisan db:seed
```

In your `.env`, set:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dts
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

(If using the Docker database instead, the host is `db`, database `testdb`, user `user`, password `pass` — see `docker-compose.yml`.)

## 3. Backend Setup

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Run the dev server with either:

```bash
php artisan serve        # http://localhost:8000
# or
npm run dev               # runs server + queue listener + logs + Vite together
```

## 4. Web Frontend Setup

```bash
cd src
npm install
npm run dev     # Vite dev server, http://localhost:5173
# or
npm run build    # production build
```

Required env var: `VITE_APP_NAME` (set in `.env.example`).

## 5. Mobile App Setup (React Native + Expo)

Not applicable — this project currently has no mobile app component. Remove this section, or replace it if a mobile client exists in a separate repository.

## Project Structure

```
document-tracker/
├── Dockerfile
├── docker-compose.yml
├── docker-entrypoint.sh
├── docker/
│   └── apache/000-default.conf
└── src/                      # Laravel application
    ├── app/
    │   ├── Http/Controllers/
    │   ├── Models/
    │   ├── Providers/
    │   ├── Console/Commands/
    │   ├── Services/
    │   └── Events/
    ├── bootstrap/
    ├── config/
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    ├── public/
    ├── resources/
    │   └── views/             # Blade templates
    ├── routes/
    │   ├── web.php
    │   ├── console.php
    │   └── channels.php
    ├── storage/
    └── tests/
```

## Common Issues

**MySQL connection refused**
Docker's database service is named `db` internally (port 3306, published externally on 3307). If running the backend manually (outside Docker) while pointing at the Docker database, use `DB_HOST=127.0.0.1` and `DB_PORT=3307` — not `db`, which only resolves inside the Docker network.

**Port already in use**
Ports used by this project: `80` (Apache in-container), `8000` (artisan serve), `5173` (Vite dev server), `8080` (Reverb), `8081` (phpMyAdmin), `8082` (app, Docker-published), `3306`/`3307` (MySQL internal/published), `6379` (Redis, optional), `2525` (mail testing).

**npm install fails with permission errors**
No custom postinstall scripts are defined in this project — this is typically an environment/permissions issue (e.g. global npm folder ownership), not project-specific. Standard fixes (correcting npm's global prefix ownership, or avoiding `sudo npm install`) apply.

## Git Workflow

- Branches in use: `main`, `develop`
- Commit messages follow conventional prefixes: `feat:`, `fix:`, `chore:`
- No formal `CONTRIBUTING.md` or PR template currently in the repo

## Team

- Monte, Jeremiah A.
- Navarro, Cecillia May E.
- Papagayo, Eloisa E.

**Institution:** Pamantasan ng Cabuyao (University of Cabuyao)
**Program:** Bachelor of Science in Information Technology
**Academic Year:** 2025–2026
