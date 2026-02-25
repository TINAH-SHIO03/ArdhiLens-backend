# ArdhiLens Flutter Frontend Integration Guide

## 1. Purpose

This guide is for the Flutter mobile team to integrate with the ArdhiLens verification APIs safely and correctly.

It covers:
- API sequence and required order
- Request/response contracts
- Token handling (`auth:sanctum` protected routes)
- Flutter-side state management expectations
- Error handling and UX behavior

## 2. Integration Prerequisites

Before calling verification APIs, frontend must have:
- Valid authenticated user session
- Bearer token for API calls
- Configured API base URL (environment-based)

All verification endpoints require:
- `Authorization: Bearer <token>`
- `Content-Type: application/json`
- `Accept: application/json`

## 3. Base URL and Environment

Recommended Flutter flavors:
- `dev`: `http://127.0.0.1:8000`
- `staging`: your staging domain
- `prod`: your production domain

Keep base URL in one place (example `AppConfig.apiBaseUrl`).

## 4. API Response Envelope

All land verification endpoints return:

```json
{
  "success": true,
  "message": "Human readable message",
  "data": {},
  "errors": {},
  "timestamp": "2026-02-23T12:00:00+00:00"
}
```

You should parse `success` first, then `data`, then `errors`.

## 5. Verification Flow (Must Be Sequential)

1. `POST /api/land-verification/plot`
2. `POST /api/land-verification/gps`
3. `POST /api/land-verification/nin/questions`
4. `POST /api/land-verification/nin/answers`

Do not skip steps.

## 6. Endpoint Contracts for Flutter

## 6.1 Step 1: Find Plot

`POST /api/land-verification/plot`

Request:

```json
{
  "plot_reference": "PLOT-001"
}
```

Important response fields:
- `data.verification_token`
- `data.plot`
- `data.next_step`

Frontend action:
- Save `verification_token` in memory for this verification session.
- Move user to GPS submission step.

## 6.2 Step 2: Verify GPS

`POST /api/land-verification/gps`

Request:

```json
{
  "verification_token": "<token_from_step_1>",
  "latitude": -6.82321,
  "longitude": 39.26952
}
```

Important response fields:
- `data.gps_check.passed`
- `data.gps_check.distance_meters`
- `data.gps_check.allowed_distance_meters`

Frontend action:
- If passed: continue to NIN input.
- If failed: show failure message and allow retry with fresh GPS.

## 6.3 Step 3: Generate NIN Questions

`POST /api/land-verification/nin/questions`

Request:

```json
{
  "verification_token": "<token_from_step_1>",
  "nin": "19990101123456789012"
}
```

Important response fields:
- `data.challenge_id`
- `data.expires_at`
- `data.questions` (array length is always 3)
  - `question_id`
  - `prompt`
  - `type`

Frontend action:
- Render exactly returned prompts.
- Capture answers by `question_id`.
- Start UI countdown based on `expires_at`.

## 6.4 Step 4: Verify Answers and Complete

`POST /api/land-verification/nin/answers`

Request:

```json
{
  "verification_token": "<token_from_step_1>",
  "challenge_id": "<challenge_id_from_step_3>",
  "answers": [
    { "question_id": "q_1", "answer": "value 1" },
    { "question_id": "q_2", "answer": "value 2" },
    { "question_id": "q_3", "answer": "value 3" }
  ]
}
```

Success response includes:
- `data.verification_log_id`
- `data.identity.full_name`
- `data.identity.gender`
- `data.identity.nin_masked`
- `data.identity.passport_image_url`
- `data.steps.*`
- `data.assessment.verdict`
- `data.assessment.risk_score`
- `data.assessment.reasons[]`
- `data.assessment.recommendation`

Frontend action:
- Show final verification success screen.
- Display identity confirmation summary.
- Display assessment summary and recommendation to user.

## 7. HTTP Status Handling Matrix

- `200`: successful step
- `401`: unauthenticated token or expired auth
- `403`: identity passed but NIN not linked to plot owner (includes direct `DO_NOT_BUY` in `errors.assessment`)
- `404`: session expired/not found, plot missing, or NIN not found
- `409`: step order violation (calling endpoint before required previous step)
- `410`: challenge expired
- `422`: validation failure or wrong challenge answers
- `429`: challenge attempts exceeded

Recommended app behavior:
- `401`: force re-login
- `410`: request new questions (repeat step 3)
- `422` answer failure: show attempts remaining if provided
- `429`: lock current challenge and restart from step 3
- `403`: render blocking risk result using `errors.assessment` and stop purchase flow

## 8. Flutter Model Contracts (Suggested)

Use typed models for stability.

```dart
class ApiEnvelope<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;
  final DateTime timestamp;
}
```

```dart
class PlotStepData {
  final String verificationToken;
  final PlotSummary plot;
  final String nextStep;
}
```

```dart
class GpsStepData {
  final GpsCheck gpsCheck;
  final String nextStep;
}
```

```dart
class NinQuestionStepData {
  final String challengeId;
  final DateTime expiresAt;
  final List<ChallengeQuestion> questions;
  final String nextStep;
}
```

```dart
class FinalVerificationData {
  final int verificationLogId;
  final String plotReference;
  final IdentitySummary identity;
  final VerificationSteps steps;
  final RiskAssessment assessment;
}
```

```dart
class RiskAssessment {
  final String verdict; // SAFE | CAUTION | DO_NOT_BUY
  final int riskScore; // 0..100
  final List<String> reasons;
  final String recommendation;
}
```

```dart
class OwnerLinkFailurePayload {
  final bool passed;
  final bool plotOwnerMatch;
  final bool historyOwnerMatch;
}
```

```dart
class VerificationErrorAssessment {
  final String verdict; // expected DO_NOT_BUY for owner-link failure
  final int riskScore;
  final List<String> reasons;
  final String recommendation;
}
```

## 9. Suggested Flutter Service Interface

```dart
abstract class LandVerificationApi {
  Future<ApiEnvelope<PlotStepData>> findPlot(String plotReference);
  Future<ApiEnvelope<GpsStepData>> verifyGps({
    required String verificationToken,
    required double latitude,
    required double longitude,
  });
  Future<ApiEnvelope<NinQuestionStepData>> generateQuestions({
    required String verificationToken,
    required String nin,
  });
  Future<ApiEnvelope<FinalVerificationData>> verifyAnswers({
    required String verificationToken,
    required String challengeId,
    required List<AnswerInput> answers,
  });
}
```

## 10. Suggested Frontend State Machine

Recommended states:
- `idle`
- `findingPlot`
- `plotFound`
- `verifyingGps`
- `gpsPassed`
- `loadingQuestions`
- `questionsReady`
- `submittingAnswers`
- `verified`
- `failed`

Keep session values in state:
- `verificationToken`
- `challengeId`
- `questions`
- `challengeExpiresAt`

## 11. Screen-to-API Mapping

- Screen 1: Plot reference input -> call step 1
- Screen 2: GPS capture/submit -> call step 2
- Screen 3: NIN input -> call step 3
- Screen 4: Dynamic question form -> call step 4
- Screen 5: Verification result summary

For step 4:
- On `200`: render `data.assessment`.
- On `403`: render `errors.assessment` and block continuation.

## 12. Identity and Image Display Rules

Only after final success show:
- `full_name`
- `gender`
- `nin_masked`
- `passport_image_url`

If `passport_image_url` is relative (e.g. `/storage/...`), prepend base domain in Flutter:
- `"$apiHost$passportImageUrl"`

## 13. Security Requirements for Frontend

- Never send `user_id` manually.
- Never cache challenge answers in logs/debug tools.
- Never persist full NIN in plaintext local storage.
- Redact sensitive values in client logs.
- Clear session state after flow success/failure timeout.

## 14. Retry and Timeout Strategy

- Network timeout recommendation: 20 seconds.
- Retry safe calls once for transient network errors:
  - step 1 and step 2 can retry
  - step 3 and step 4 should avoid duplicate aggressive retries
- If challenge expires (`410`), restart from step 3.

## 15. QA Checklist for Flutter Team

1. Verify flow works with valid token and correct sequence.
2. Verify `401` redirects to login.
3. Verify GPS failure (`422`) shows clear retry UX.
4. Verify wrong answers return proper error handling.
5. Verify challenge expiry path (`410`) refreshes questions.
6. Verify owner-link failure (`403`) is handled gracefully.
7. Verify final success screen shows identity + assessment summary correctly.
8. Verify relative passport image URLs render correctly.
9. Verify `403` payload can render `DO_NOT_BUY` recommendation from `errors.assessment`.

## 16. Quick End-to-End Test Payloads

Step 1:

```json
{ "plot_reference": "PLOT-001" }
```

Step 2:

```json
{
  "verification_token": "from_step_1",
  "latitude": -6.82321,
  "longitude": 39.26952
}
```

Step 3:

```json
{
  "verification_token": "from_step_1",
  "nin": "19990101123456789012"
}
```

Step 4:

```json
{
  "verification_token": "from_step_1",
  "challenge_id": "from_step_3",
  "answers": [
    { "question_id": "from_step_3_q1", "answer": "value1" },
    { "question_id": "from_step_3_q2", "answer": "value2" },
    { "question_id": "from_step_3_q3", "answer": "value3" }
  ]
}
```

## 17. Backend Files Referenced

- `routes/api.php`
- `app/Http/Controllers/Api/LandVerificationController.php`
- `config/land_verification.php`
- `docs/API_DOCUMENTATION.md`
- `docs/SYSTEM_DOCUMENTATION.md`
