# ArdhiLens API Documentation

## 1. Overview

This document covers the current REST APIs implemented in the project.

Primary flow:
1. Find plot by reference.
2. Verify user GPS proximity to plot.
3. Generate dynamic NIN challenge questions.
4. Verify answers, check NIN-to-plot ownership link, compute deterministic risk score, get Gemini advisory, and create verification log.

## 2. Base URL

- Local example: `http://127.0.0.1:8000`
- API prefix: `/api`

Full example:
- `POST http://127.0.0.1:8000/api/land-verification/plot`

## 3. Authentication

- All `POST /api/land-verification/*` endpoints are protected by `auth:sanctum`.
- Caller must send `Authorization: Bearer <token>`.
- `user_id` is not accepted from client input; verification logs use the authenticated user from token (`request()->user()->id`).

### 3.1 Auth Endpoints (Token Issuance)

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout` (auth required)
- `GET /api/auth/me` (auth required)

`/api/auth/login` and `/api/auth/register` return a `token` that must be sent as Bearer token for all land verification endpoints.

## 4. Standard Response Format

All custom land-verification endpoints use a consistent response envelope.

### Success format

```json
{
  "success": true,
  "message": "Human readable message",
  "data": {},
  "errors": {},
  "timestamp": "2026-02-23T12:00:00+00:00"
}
```

### Error format

```json
{
  "success": false,
  "message": "Human readable error",
  "data": {},
  "errors": {},
  "timestamp": "2026-02-23T12:00:00+00:00"
}
```

## 5. Verification Session Behavior

- A `verification_token` (UUID) is created at step 1.
- Session is cached for 30 minutes.
- NIN challenge TTL is 5 minutes.
- Max NIN challenge attempts is 3.
- Configurable GPS distance threshold: `LAND_VERIFICATION_MAX_DISTANCE_METERS` (default `150`).

## 6. Endpoints

---

## 6.1 Find Plot

`POST /api/land-verification/plot`

### Purpose

Finds a plot by `plot_reference`, initializes verification session, and returns safe plot details.

### Request body

| Field | Type | Required | Rules |
|---|---|---|---|
| `plot_reference` | string | Yes | max 50 |

### Example request

```json
{
  "plot_reference": "PLOT-001"
}
```

### Success response (200)

```json
{
  "success": true,
  "message": "Plot found.",
  "data": {
    "verification_token": "2fe31f8c-6fb0-44cc-9eb3-3b02a310170d",
    "plot": {
      "id": 10,
      "plot_reference": "PLOT-001",
      "region": "Dar es Salaam",
      "district": "Ilala",
      "ward": "Kivukoni",
      "village_mtaa": "Kivukoni",
      "street": "Ohio St",
      "gps_latitude": "-6.82320000",
      "gps_longitude": "39.26950000",
      "size_hectares": "0.2500",
      "land_use": "Commercial",
      "tenure_type": "Granted",
      "certificate_type": "Title",
      "issue_date": "2024-08-01",
      "expiry_date": null,
      "status": "Active"
    },
    "next_step": "Submit GPS coordinates for distance verification."
  },
  "errors": {},
  "timestamp": "2026-02-23T12:00:00+00:00"
}
```

### Common errors

- `401`: unauthenticated (missing/invalid token).
- `422`: validation failed.
- `404`: plot not found.

---

## 6.2 Verify GPS

`POST /api/land-verification/gps`

### Purpose

Validates user-submitted coordinates against plot coordinates using Haversine distance.

### Request body

| Field | Type | Required | Rules |
|---|---|---|---|
| `verification_token` | string | Yes | must be existing active session |
| `latitude` | number | Yes | between -90 and 90 |
| `longitude` | number | Yes | between -180 and 180 |

### Example request

```json
{
  "verification_token": "2fe31f8c-6fb0-44cc-9eb3-3b02a310170d",
  "latitude": -6.82321,
  "longitude": 39.26952
}
```

### Success response (200)

```json
{
  "success": true,
  "message": "GPS verification passed.",
  "data": {
    "gps_check": {
      "passed": true,
      "distance_meters": 3.12,
      "allowed_distance_meters": 150,
      "submitted_latitude": -6.82321,
      "submitted_longitude": 39.26952,
      "verified_at": "2026-02-23T12:01:10+00:00"
    },
    "next_step": "Submit NIN to generate dynamic security questions."
  },
  "errors": {},
  "timestamp": "2026-02-23T12:01:10+00:00"
}
```

### Common errors

- `401`: unauthenticated (missing/invalid token).
- `404`: verification session expired or not found.
- `422`: validation failed, plot has no GPS coordinates, or distance check failed.

---

## 6.3 Generate Dynamic NIN Questions

`POST /api/land-verification/nin/questions`

### Purpose

Validates NIN existence (`nidas.status = Active`) and generates dynamic 3-question challenge without exposing answers.

### Request body

| Field | Type | Required | Rules |
|---|---|---|---|
| `verification_token` | string | Yes | active session |
| `nin` | string | Yes | exact length 20 |

### Example request

```json
{
  "verification_token": "2fe31f8c-6fb0-44cc-9eb3-3b02a310170d",
  "nin": "19990101123456789012"
}
```

### Question selection logic

Exactly 3 questions per session:
1. Mother field: preferred `mother_middle_name`, fallback `mother_surname`.
2. Father field: preferred `father_middle_name`, fallback `father_surname`.
3. Location field: random from non-empty set:
   - `perm_ward`, `perm_mtaa`, `perm_district`
   - `res_ward`, `res_mtaa`, `res_district`

### Success response (200)

```json
{
  "success": true,
  "message": "Dynamic NIN questions generated.",
  "data": {
    "challenge_id": "a1ce8b6a-39b0-4f42-97c0-f5e2109091a7",
    "expires_at": "2026-02-23T12:06:00+00:00",
    "questions": [
      {
        "question_id": "q_abcd1234efgh",
        "prompt": "Enter your mother middle name.",
        "type": "text"
      },
      {
        "question_id": "q_ijkl5678mnop",
        "prompt": "What is your father middle name as recorded by NIDA?",
        "type": "text"
      },
      {
        "question_id": "q_qrst9012uvwx",
        "prompt": "Enter your current residence ward.",
        "type": "text"
      }
    ],
    "next_step": "Submit answers to complete identity verification."
  },
  "errors": {},
  "timestamp": "2026-02-23T12:01:30+00:00"
}
```

### Common errors

- `401`: unauthenticated (missing/invalid token).
- `409`: GPS step not passed yet.
- `404`: session not found, or NIN not found/active.
- `422`: insufficient NIDA profile fields to generate all 3 questions.

---

## 6.4 Verify NIN Answers and Finalize Verification

`POST /api/land-verification/nin/answers`

### Purpose

Validates question answers, enforces NIN-plot ownership link, writes `verification_logs`, and returns:
- identity confirmation payload
- final assessment (`verdict`, `risk_score`, `reasons`, `recommendation`)

### Request body

| Field | Type | Required | Rules |
|---|---|---|---|
| `verification_token` | string | Yes | active session |
| `challenge_id` | uuid | Yes | must match current challenge |
| `answers` | array | Yes | must contain exactly 3 entries |
| `answers.*.question_id` | string | Yes | must match generated IDs |
| `answers.*.answer` | string | Yes | max 255 |

### Example request

```json
{
  "verification_token": "2fe31f8c-6fb0-44cc-9eb3-3b02a310170d",
  "challenge_id": "a1ce8b6a-39b0-4f42-97c0-f5e2109091a7",
  "answers": [
    { "question_id": "q_abcd1234efgh", "answer": "Amina" },
    { "question_id": "q_ijkl5678mnop", "answer": "Bakari" },
    { "question_id": "q_qrst9012uvwx", "answer": "Kivukoni" }
  ]
}
```

### Validation behavior

- Inputs are normalized for comparison:
  - lowercase
  - trim spaces
  - punctuation removed
  - repeated whitespace collapsed
- Stored server-side answers are hashed and never returned to client.
- Challenge expires after 5 minutes.
- Max 3 attempts.

### Ownership link checks (after questions pass)

Both must pass:
1. `plots.owner_nida == nin`
2. Latest ownership history consistency check:
   - latest `plot_ownership_histories.to_nida == nin`
   - if no history exists, this check defaults to pass

### Success response (200)

```json
{
  "success": true,
  "message": "Verification completed successfully.",
  "data": {
    "verification_log_id": 92,
    "plot_reference": "PLOT-001",
    "identity": {
      "full_name": "John M Doe",
      "gender": "M",
      "nin_masked": "1999********9012",
      "passport_image_url": "/storage/nida/passports/abc123.jpg"
    },
    "steps": {
      "plot_found": true,
      "gps_passed": true,
      "nida_questions_passed": true,
      "owner_link_passed": true
    },
    "assessment": {
      "verdict": "CAUTION",
      "risk_score": 62,
      "reasons": [
        "There is one ongoing legal dispute on this plot.",
        "Land rates appear overdue by more than 12 months."
      ],
      "recommendation": "Proceed only after resolving flagged legal and payment issues with certified records."
    }
  },
  "errors": {},
  "timestamp": "2026-02-23T12:02:00+00:00"
}
```

### Owner-link failure response (403)

If identity challenge passes but owner linkage fails, the API returns direct `DO_NOT_BUY` in `errors.assessment`.

```json
{
  "success": false,
  "message": "Identity verified but owner linkage failed for this plot.",
  "data": {},
  "errors": {
    "owner_link": {
      "passed": false,
      "plot_owner_match": false,
      "history_owner_match": false
    },
    "assessment": {
      "verdict": "DO_NOT_BUY",
      "risk_score": 95,
      "reasons": [
        "Submitted NIN does not match the legal owner record for this plot.",
        "Latest ownership transfer history is inconsistent with the submitted NIN."
      ],
      "recommendation": "Do not proceed with purchase until ownership records are corrected and formally verified at the land office."
    }
  },
  "timestamp": "2026-02-23T12:02:00+00:00"
}
```

### Common errors

- `401`: unauthenticated (missing/invalid token).
- `409`: prerequisite steps not passed.
- `410`: challenge expired.
- `422`: invalid challenge, failed answers, invalid payload.
- `429`: max attempts reached.
- `403`: answers passed but NIN is not linked to plot owner (includes direct `DO_NOT_BUY` assessment in `errors.assessment`).
- `404`: session/plot not found.

## 7. Verification Log Creation

When all checks pass, one row is inserted into `verification_logs` with:
- `user_id` (derived from authenticated user token)
- `plot_id`
- step flags (`geolocation_passed`, `nida_passed`, `certificate_passed`)
- submitted GPS coordinates
- `ai_verdict` from deterministic thresholds (`SAFE`, `CAUTION`, `DO_NOT_BUY`)
- `risk_score` from deterministic risk engine (`0..100`)
- `ai_reasons` and `ai_recommendation` from Gemini JSON response (or deterministic fallback if Gemini fails)
- `status = Completed`
- `ai_payload` with:
  - verification context
  - risk engine breakdown
  - Gemini raw/validation metadata
  - normalized land data snapshot

If owner-link fails, a failed verification log is written with:
- `ai_verdict = DO_NOT_BUY`
- forced high `risk_score` (default `95`, configurable)
- `status = Failed`

## 8. Other API Route

`GET /api/user`
- Default Laravel route with `auth:sanctum` middleware.
- Returns authenticated user profile.

## 9. Environment and Config

- `LAND_VERIFICATION_MAX_DISTANCE_METERS` in `.env` controls distance threshold.
- `LAND_RISK_SAFE_MAX_SCORE` and `LAND_RISK_CAUTION_MAX_SCORE` control verdict thresholds.
- `LAND_VERIFICATION_OWNER_LINK_FORCED_SCORE` controls forced score for owner-link mismatch.
- `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_TIMEOUT_SECONDS`, `GEMINI_RETRIES`, `GEMINI_TEMPERATURE` control AI advisory behavior.
- Config file: `config/land_verification.php`.
  - Gemini service config: `config/services.php`.

Example:

```env
LAND_VERIFICATION_MAX_DISTANCE_METERS=150
LAND_RISK_SAFE_MAX_SCORE=29
LAND_RISK_CAUTION_MAX_SCORE=69
LAND_VERIFICATION_OWNER_LINK_FORCED_SCORE=95
GEMINI_API_KEY=***
GEMINI_MODEL=gemini-2.0-flash
```

## 10. Postman Testing Sequence

1. Set `Authorization` Bearer token in Postman collection.
2. Call `/plot`; store `verification_token`.
3. Call `/gps`.
4. Call `/nin/questions`; store `challenge_id` and question IDs.
5. Call `/nin/answers` with exact returned question IDs.

## 11. Security Notes

- Question answers are not returned to client.
- NIN is masked in final response.
- Ownership is verified after identity challenge.
- Land-verification flow is authentication-enforced (`auth:sanctum`), so clients cannot submit a fake `user_id`.
