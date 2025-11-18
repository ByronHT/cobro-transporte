# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a transportation payment system ("cobro-transporte") built with Laravel 9 backend and React frontend. The system manages bus routes, passenger cards, trips, and transactions for a public transportation network.

## Tech Stack

- **Backend**: Laravel 9 (PHP 8.0.2+)
- **Frontend**: React 19 with React Router
- **UI Framework**: Ionic React components + Tailwind CSS
- **Build Tool**: Vite 7
- **Database**: MySQL
- **Authentication**: Laravel Sanctum (API tokens)

## Development Commands

### Backend (Laravel)
```bash
# Install PHP dependencies
composer install

# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Refresh database with migrations
php artisan migrate:fresh

# Run database seeders
php artisan db:seed

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests
php artisan test
# Or with PHPUnit directly
vendor/bin/phpunit

# Code formatting (Laravel Pint)
vendor/bin/pint

# Generate application key (required after cloning)
php artisan key:generate
```

### Frontend (React + Vite)
```bash
# Install Node dependencies
npm install

# Start Vite development server
npm run dev

# Build for production
npm run build
```

### Running the Application
1. Start Laravel backend: `php artisan serve` (runs on port 8000)
2. Start Vite dev server: `npm run dev` (runs on port 5173)
3. Access the app at `http://localhost:8000` or the configured Vite host

## Architecture

### User Roles & Authentication

The system supports three user roles via the `role` field on the `users` table:
- **admin**: Full system access, manages users, cards, buses, routes, trips
- **driver**: Operates buses, manages trip start/end, processes passenger payments
- **passenger**: Views profile, transaction history, and trip records

Role-based access is enforced via `RoleMiddleware` (`app/Http/Middleware/RoleMiddleware.php`).

### Core Models & Relationships

**User** (Authenticatable)
- Has many `Card`s (passenger_id)
- Has many `Trip`s as driver (driver_id)
- Fields: name, email, password, role, active, balance

**Card**
- Belongs to `User` (passenger)
- Has many `Transaction`s
- Has many `Trip`s
- Fields: uid (RFID card identifier), balance, passenger_id, active

**Trip**
- Belongs to `Bus`, `Ruta`, `User` (driver), `Card`
- Has many `Transaction`s
- Fields: fecha, ruta_id, bus_id, driver_id, card_id, inicio, fin, fare

**Transaction**
- Belongs to `Card`, `Trip`
- Records payment events

**Bus**
- Belongs to `Ruta`
- Has many `Trip`s
- Represents physical buses operating on routes

**Ruta** (Route)
- Has many `Bus`es
- Has many `Trip`s
- Fields include descripcion (route description)

### Frontend Architecture (React SPA)

The frontend is a React single-page application with role-based routing:

**Entry Point**: `resources/js/app.jsx`
- Uses React Router for client-side routing
- Protected routes check localStorage for `auth_token` and `user_role`

**Main Components** (`resources/js/components/`):
- `Login.jsx`: Handles authentication for all user types
- `Dashboard.jsx`: Passenger dashboard (view profile, transactions, trips)
- `DriverDashboard.jsx`: Driver dashboard (start/end trips, process payments, view current trip)

**Routing Logic**:
- `/login` → Login page
- `/dashboard` → Passenger dashboard (requires passenger role)
- `/driver/dashboard` → Driver dashboard (requires driver role)
- `/` → Redirects based on auth status and role

### Backend Routing Structure

**Web Routes** (`routes/web.php`):
- Traditional Blade views for admin panel
- Authentication routes (login, logout)
- Admin CRUD routes (users, cards, buses, rutas, trips) with `auth` + `role:admin` middleware
- Admin monitoring routes (trips, card transactions)
- Passenger routes with `auth` + `role:passenger` middleware
- SPA fallback route (`/{any}`) serves React app for all unhandled routes

**API Routes** (`routes/api.php`):
- Payment processing: `POST /api/payment/process`
- Trip management: `POST /api/trips/start`, `POST /api/trips/end`
- Driver actions:
  - Request trip start/end
  - Process payment
  - Get available buses
- Device polling: `GET /api/device/command/{bus}` (for IoT devices to receive commands)
- Protected routes under `auth:sanctum` middleware for user profile, transactions, trips

### Payment Flow

1. **Driver App** → Calls API endpoint to request payment processing
2. **Backend** → Validates card balance, creates transaction, updates card/user balance
3. **Device Polling** → IoT devices poll `/api/device/command/{bus}` for pending actions
4. System records transaction linked to current trip and card

### Admin Panel (Blade Views)

Located in `resources/views/admin/`:
- Uses traditional Laravel Blade templates
- CRUD interfaces for managing:
  - Users (all roles)
  - Cards (link to passengers, view balance)
  - Buses (assign to routes)
  - Rutas (routes)
  - Trips (view/manage trip records)
- Monitoring views for real-time trip and transaction tracking

### Client Panel (React + Blade)

- Entry view: `resources/views/cliente/` (if exists) or SPA handles routing
- React components handle all UI interactions
- Uses Ionic React components for mobile-friendly interface

## Configuration Notes

### Vite Configuration
The `vite.config.js` is currently configured with a specific host IP (`192.168.0.16`). When working on this project:
- Update `server.host` and `server.hmr.host` to your local network IP or `localhost`
- Update `server.origin` accordingly

### Environment Setup
1. Copy `.env.example` to `.env`
2. Set database credentials (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
3. Run `php artisan key:generate`
4. Configure Vite host in `vite.config.js` if needed

### Database Migrations
Migrations are in `database/migrations/`. Key tables:
- Users with roles (admin, driver, passenger)
- Cards with RFID UIDs
- Buses assigned to routes
- Trips tracking journey start/end
- Transactions recording payments

## Working with the Codebase

### Adding New Admin Features
1. Create controller in `app/Http/Controllers/Admin/`
2. Add resource route in `routes/web.php` within the admin middleware group
3. Create Blade views in `resources/views/admin/`

### Adding New API Endpoints
1. Create controller in `app/Http/Controllers/API/`
2. Add route in `routes/api.php`
3. Use `auth:sanctum` middleware for protected endpoints
4. Return JSON responses

### Adding New React Components
1. Create component in `resources/js/components/`
2. Add route in `resources/js/app.jsx`
3. Use `ProtectedRoute` wrapper for role-based access
4. Vite will hot-reload changes automatically

### Modifying Database Schema
1. Create new migration: `php artisan make:migration migration_name`
2. Define schema changes in the migration file
3. Run `php artisan migrate`
4. Update corresponding model's `$fillable` array

## Important Patterns

### Role Checking in Blade
```php
@if(auth()->user()->role === 'admin')
    <!-- Admin-only content -->
@endif
```

### API Authentication
Frontend stores `auth_token` in localStorage after login, includes in API requests via Authorization header.

### Transaction Safety
Payment processing should validate card balance, user active status, and create database transactions atomically.
