# Finance App

Separate finance application for school billing with mobile-first UI.

## Features

- Fee type management
- Bill generation using merged student list (Anubhava + Central)
- EMI plan creation per bill
- Dynamic PDF invoice download (generated on request, not stored)

## Project Structure

- `frontend`: React + TypeScript (Vite)
- `backend`: PHP API

## Backend Setup

1. Copy `backend/.env.example` to `backend/.env` and fill DB credentials.
2. Install dependencies:
   - `cd backend`
   - `composer install`
3. Run migration:
   - `php migrate.php`
4. Start API server:
   - `php -S localhost:8081 backend/router.php`

## Frontend Setup

1. Copy `frontend/.env.example` to `frontend/.env`.
2. Install dependencies:
   - `cd frontend`
   - `npm install`
3. Start frontend:
   - `npm run dev`

## API Endpoints

- `GET /api/students`
- `GET /api/fee-types`
- `POST /api/fee-types`
- `PUT /api/fee-types`
- `POST /api/bills/generate`
- `POST /api/bills/{bill_id}/emi-plan`
- `GET /api/invoices/{bill_id}/pdf`

## Validation

After configuring backend env values:

- `php backend/scripts/smoke-students.php` validates both school contexts (`1` and `2`) against both data sources.
# lekkapatra
