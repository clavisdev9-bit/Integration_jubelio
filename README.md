# Jubelio - Odoo Integration

<p align="center">
    <img src="https://laravel.com/img/logomark.min.svg" width="120" alt="Laravel Logo">
</p>

## About This Project

This project is a middleware integration service built with **Laravel** to synchronize data between **Jubelio** and **Odoo ERP**.

The application acts as a bridge that automates business processes by ensuring data consistency across both platforms.

## Features

* Synchronize Products from Jubelio to Odoo
* Synchronize Purchase Orders to Odoo
* Synchronize Sales Orders
* Inventory and Stock Updates
* Background Job Processing using Laravel Queue
* Logging and Error Handling
* REST API Integration
* Scalable and Maintainable Architecture

## Technology Stack

* **Laravel**
* **PostgreSQL**
* **Docker**
* **Redis** (optional for queues and caching)
* **Jubelio API**
* **Odoo XML-RPC / JSON-RPC API**

## Project Structure

```text
app/
├── Console/
├── Http/
├── Jobs/
├── Models/
├── Services/
├── Repositories/
└── Helpers/

routes/
config/
database/
```

## Installation

Clone the repository:

```bash
git clone <repository-url>
cd project-name
```

Install dependencies:

```bash
composer install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Run database migrations:

```bash
php artisan migrate
```

Start the application:

```bash
php artisan serve
```

## Queue Worker

Run queue worker for background synchronization:

```bash
php artisan queue:work
```

## Docker

Build and run containers:

```bash
docker compose up -d --build
```

## Main Integrations

### Jubelio

* Product Master
* Sales Orders
* Purchase Orders
* Stock Information

### Odoo ERP

* Product Template
* Purchase Orders
* Sales Orders
* Inventory Management

## Logging

Application logs are stored in:

```text
storage/logs/laravel.log
```

## License

This project is developed internally for business integration purposes.
