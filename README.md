# Tailoring Management System

A comprehensive Laravel 12 + React application for managing tailoring business operations.

## Docker

Build the production image locally:

```bash
docker build -t app .
```

Run the container on port `8080`:

```bash
docker run -p 8080:8080 \
  -e APP_KEY=base64:your-generated-key \
  -e APP_URL=http://localhost:8080 \
  app
```

The image expects runtime environment variables and does not copy `.env` into the container. For Railway, set `APP_KEY`, database credentials, and any app secrets in Railway variables. The container listens on `PORT` and defaults to `8080` when `PORT` is not provided.

## Features

- **Orders Management** - Create, track, and manage customer orders
- **Measurements** - Store and manage customer body measurements
- **Billing** - Generate bills and track payments
- **Invoice** - Professional invoice generation
- **Dashboard** - Real-time business analytics and insights

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** React + Vite
- **Database:** MySQL
- **Authentication:** Laravel Sanctum (API Token)
- **Testing:** Pest

## Setup Instructions

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Install frontend dependencies
npm install

# Run frontend dev server
npm run dev

# Run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed
```

## GitHub Preparation

- `.env` is ignored and must not be committed.
- `vendor/`, `node_modules/`, logs, and frontend build artifacts are ignored.
- Use `.env.example` as the shared environment template.

## Security

- Never commit `.env` file
- API keys and credentials are stored in environment variables
- Passwords are hashed using bcrypt

## License

MIT License
