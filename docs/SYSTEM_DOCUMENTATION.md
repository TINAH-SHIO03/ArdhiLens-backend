# ArdhiLens System Documentation

## 1. System Overview

ArdhiLens is a Laravel 12 + Filament 4 land administration backend for:
- NIDA identity registry management.
- Plot registry management.
- Plot legal/financial operational records.
- Ownership transfer history tracking.
- Land verification workflow with GPS + dynamic NIN challenge.
- Verification audit logging and dashboard insights.

## 2. Business Goals

- Maintain a trusted land registry linked to national identity (NIN).
- Reduce fraud by combining location, identity challenge, and ownership checks.
- Keep an audit trail for every verification decision.
- Provide admin operations through Filament with domain-specific sections.

## 3. Technology Stack

- PHP `^8.2`
- Laravel `^12.0`
- Filament `~4.0`
- Laravel Sanctum `^4.0`
- MySQL (current `.env`), with cache/session database drivers enabled
- Storage-based media handling for passport images

Core configuration files:
- `composer.json`
- `.env`
- `config/app.php`
- `config/land_verification.php`

## 4. High-Level Architecture

Layers:
1. Presentation/Admin Layer:
   - Filament Admin Panel (`/admin`)
   - Custom dashboard widgets and resource pages
2. API Layer:
   - REST endpoints under `/api/land-verification/*`
3. Domain/Data Layer:
   - Eloquent models for NIDA, plots, plot operations, users, verification logs
4. Persistence:
   - MySQL tables from migrations
   - File storage for passport images (`public` disk)
   - Cache for verification sessions/challenges

## 5. Domain Modules

### 5.1 Identity (NIDA)

Main model: `App\Models\Nida`

Responsibilities:
- Store NIN and biographical profile.
- Store parent names and residence details used for challenge questions.
- Store passport image path (`passport_image_path`) for display.
- Track status (`Active`, `Suspended`, `Deceased`, `Pending`).

Filament resource:
- `app/Filament/Resources/Nidas/*`

### 5.2 Users and Access Context

Main model: `App\Models\User`

Responsibilities:
- Login account management.
- Role assignment (`admin`, `seller`, `buyer`).
- Optional link to NIDA via `users.nin`.
- Holds verification ownership context (`verification_logs.user_id`).

Filament resource:
- `app/Filament/Resources/Users/*`

### 5.3 Plot Registry

Main model: `App\Models\Plot`

Responsibilities:
- Store plot reference and owner NIN (`owner_nida`).
- Store administrative location + GPS coordinates.
- Store land/certificate/compliance status.
- Connect to legal and financial plot operation records.

Filament resource:
- `app/Filament/Resources/Plots/*`

### 5.4 Plot Operations (Separated by Function)

Each module is its own Filament section/resource, with owner NIN verification in forms:
- Encumbrances: `PlotEncumbrance` (bank loans/charges)
- Disputes: `PlotDispute` (court/legal disputes)
- Caveats: `PlotCaveat` (restrictions/claims)
- Land Rates: `PlotLandRate` (tax/payment periods)
- Ownership History: `PlotOwnershipHistory` (transfer chain)

Filament resources:
- `app/Filament/Resources/PlotEncumbrances/*`
- `app/Filament/Resources/PlotDisputes/*`
- `app/Filament/Resources/PlotCaveats/*`
- `app/Filament/Resources/PlotLandRates/*`
- `app/Filament/Resources/PlotOwnershipHistories/*`

### 5.5 Verification Logs

Main model: `App\Models\VerificationLog`

Responsibilities:
- Persist step-by-step verification outcomes.
- Keep AI-related fields and payload snapshots.
- Support dashboard metrics and audit review.

Filament resource:
- `app/Filament/Resources/VerificationLogs/*`
- Read-only by design (no form for create/edit).

### 5.6 Dashboard

Dashboard page:
- `app/Filament/Pages/LandDashboard.php`

Widgets:
- `LandSnapshotStats`
- `VerificationVerdictChartWidget`
- `RecentHighRiskVerificationsWidget`
- `RecentOwnershipTransfersWidget`

Purpose:
- Operational awareness for disputes, high-risk verifications, transfer activity, and totals.

## 6. Database Design

## 6.1 Core Tables

### `nidas`

Key identity fields:
- `nin` (char(20), unique)
- names, gender, date of birth
- parent names
- residence and permanent address fields
- doc references (`birth_certificate_number`, `passport_number`, `voter_id`)
- media fields (`photo_base64`, `signature_base64`)
- `passport_image_path` (added by later migration)
- soft deletes enabled

### `users`

Key fields:
- auth fields (`name`, `email`, `password`)
- `nin` (nullable unique FK to `nidas.nin`)
- `role` enum (`admin`, `seller`, `buyer`)
- `is_active`, `verified_at`

### `plots`

Key fields:
- `plot_reference` unique
- `owner_nida` FK to `nidas.nin`
- location fields + optional GPS
- land/certificate enums
- compliance flags
- soft deletes enabled

### `verification_logs`

Key fields:
- `user_id` FK to users
- `plot_id` FK to plots
- step flags (`geolocation_passed`, `nida_passed`, `certificate_passed`)
- submitted GPS
- AI verdict and payload
- status enum (`Completed`, `Failed`, `Incomplete`)

## 6.2 Plot Operation Tables

- `plot_encumbrances`
- `plot_disputes`
- `plot_caveats`
- `plot_land_rates`
- `plot_ownership_histories`

All link to `plots.id`, with ownership history additionally linking `from_nida` and `to_nida` to `nidas.nin`.

## 6.3 Relationship Summary

- `Nida` 1..1 `User` (optional via `nin`)
- `Nida` 1..* `Plot` (as owner via `owner_nida`)
- `Plot` 1..* each operations table
- `User` 1..* `VerificationLog`
- `Plot` 1..* `VerificationLog`

## 7. Admin Panel Structure (Filament)

Panel provider:
- `app/Providers/Filament/AdminPanelProvider.php`

Key settings:
- panel path: `/admin`
- login enabled
- brand name: `config('app.name')` (currently `ArdhiLens`)
- primary color: amber
- resources/pages/widgets auto-discovered

Navigation grouping (high level):
- `Plot Core`:
  - Plots
- `Plot Operations`:
  - Encumbrances
  - Disputes
  - Caveats
  - Land Rates
  - Ownership History
  - Verification Logs
- Additional core resources:
  - Nidas
  - Users

## 8. Key Functional Workflows

## 8.1 Plot Registration

Via Plot wizard form:
1. Owner verification (active NIN required).
2. Location capture (administrative + GPS).
3. Land and compliance details.

Business rule:
- `owner_nida` must reference active, non-deleted NIDA.

## 8.2 Plot Operation Entry (Encumbrance/Dispute/Caveat/Land Rate/Ownership)

Shared pattern in each resource:
1. Enter owner NIN first (`owner_nida_check`).
2. Plot dropdown is filtered to plots owned by that NIN.
3. Validation enforces filtered owner-to-plot consistency.

This reduces accidental or unauthorized cross-owner data entry.

## 8.3 Ownership History Visualization

Ownership history resource includes:
- timeline view (`Origin -> Transfer -> Current`) with date labels
- full-chain generation for quick traceability

This supports investigations where ownership changed multiple times.

## 8.4 Authenticated Verification API Flow

Implemented in:
- `app/Http/Controllers/Api/LandVerificationController.php`

Flow:
1. Plot lookup by reference.
2. GPS distance check.
3. NIN challenge generation (dynamic 3 questions).
4. Answer validation.
5. Owner link verification.
6. Verification log creation.

Dynamic questions:
- Mother (middle preferred, surname fallback)
- Father (middle preferred, surname fallback)
- One random location question from permanent/current location fields

## 9. Verification and Security Controls

Implemented controls:
- NIN challenge answers never exposed.
- Answers normalized and compared via hash.
- Challenge expiration and max attempts.
- Owner link check after identity challenge.
- NIN masked in final success payload.
- Verification session state kept server-side in cache.
- Land verification endpoints are protected with `auth:sanctum`.
- `verification_logs.user_id` is always taken from authenticated token context, not request body.

## 10. Storage and Media Handling

Passport image in NIDA:
- Column: `nidas.passport_image_path`
- Upload via Filament `FileUpload` to `public` disk (`nida/passports`)
- Display via `ImageEntry`

Operational requirement:
- `php artisan storage:link`

## 11. Configuration

### App identity
- `APP_NAME=ArdhiLens`

### Verification distance threshold
- `LAND_VERIFICATION_MAX_DISTANCE_METERS`
- read from `config/land_verification.php`

## 12. API/AI Field Semantics in Logs

`verification_logs` stores AI and risk fields for completed and failed verification outcomes:
- `ai_verdict`
- `risk_score`
- `ai_reasons`
- `ai_recommendation`
- `ai_payload`

Current implemented behavior:
- Deterministic risk engine computes `risk_score` (`0..100`) and threshold verdict (`SAFE`, `CAUTION`, `DO_NOT_BUY`).
- Gemini is called for strict-JSON advisory output (`verdict`, `risk_score`, `reasons`, `recommendation`).
- Deterministic lock is enforced: score/verdict stay aligned with system calculation.
- On Gemini failure/invalid output, deterministic fallback is used and logged in `ai_payload.gemini`.
- Owner-link mismatch creates a failed verification with direct `DO_NOT_BUY`.

Reference specification:
- `docs/LAND_RISK_SCORING_AND_GEMINI_INTEGRATION.md`

## 13. Operations and Maintenance

Common commands:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Quality checks:

```bash
php artisan route:list
php artisan test
vendor/bin/pint
```

## 14. File and Folder Reference

Important paths:
- API routes: `routes/api.php`
- Verification controller: `app/Http/Controllers/Api/LandVerificationController.php`
- Models: `app/Models/*`
- Migrations: `database/migrations/*`
- Filament provider: `app/Providers/Filament/AdminPanelProvider.php`
- Dashboard: `app/Filament/Pages/LandDashboard.php`
- Widgets: `app/Filament/Widgets/*`
- Docs: `docs/API_DOCUMENTATION.md`, `docs/SYSTEM_DOCUMENTATION.md`, `docs/LAND_RISK_SCORING_AND_GEMINI_INTEGRATION.md`

## 15. Known Constraints and Next Improvements

1. Add rate limiting per IP/user/challenge endpoint.
2. Add automated tests for all 4 verification API endpoints.
3. Add delegated authorization model for non-owner actors (lawyers/family/agents).
4. Calibrate risk weights and thresholds with production audit outcomes.
5. Add alerting for high-risk or repeated failed verification attempts.
