# Land Risk Scoring and Gemini Integration Design

## 1. Purpose

This document defines how ArdhiLens should classify land verification outcomes using:

1. Deterministic verification gates (GPS, NIDA challenge, owner linkage).
2. Deterministic land risk scoring (0 to 100).
3. Gemini AI for final buyer-facing explanation and recommendation.

The goal is consistency, auditability, and clear behavior for edge cases.

## 2. Scope

In scope:

- Final decision policy for `SAFE`, `CAUTION`, `DO_NOT_BUY`, and `INCOMPLETE`.
- Risk scoring rules and weights for Tanzanian land registry context.
- Gemini prompt and strict JSON response contract.
- Fallback behavior when Gemini fails or returns invalid output.

Out of scope:

- UI changes.
- New database schema changes.
- Refactoring unrelated verification endpoints.

## 3. Current System Context

Current verification flow in `LandVerificationController`:

1. Plot lookup by `plot_reference`.
2. GPS distance check.
3. Dynamic NIN challenge generation.
4. NIN challenge answer validation.
5. Owner linkage check (`plots.owner_nida` and latest ownership transfer).
6. Save verification log.

Previous placeholder behavior (before implementation):

- `ai_verdict = SAFE`
- `risk_score = 0`

This document replaces placeholder AI fields with a real decision framework.

## 4. Decision Architecture

The system must use a two-layer decision pipeline:

1. Layer A: Verification gates (identity and linkage confidence).
2. Layer B: Land legal/financial risk scoring.

Gemini is applied after deterministic logic to provide explainability, not to override deterministic controls.

## 5. Layer A: Verification Gate Policy

### 5.1 Gate Outcomes

| Gate | Failure Meaning | Outcome | Verdict Handling |
|---|---|---|---|
| Plot not found / session missing | Cannot verify context | Stop flow | Error response |
| GPS mismatch | Location confidence not sufficient | Retry or mark incomplete | `INCOMPLETE`, not direct `DO_NOT_BUY` |
| NIDA challenge fail/expired/max attempts | Identity confidence not sufficient | Retry or restart challenge | `INCOMPLETE`, not direct `DO_NOT_BUY` |
| Owner linkage fail | Identity does not match legal ownership chain | Fraud/legal red flag | Direct `DO_NOT_BUY` |

### 5.2 GPS Policy

GPS should not be treated as a standalone legal ownership verdict.

Rules:

1. Single GPS mismatch should not cause `DO_NOT_BUY`.
2. GPS mismatch should produce retry path first.
3. Repeated GPS mismatch should move to manual review or incomplete state.
4. GPS can optionally add a small confidence penalty in future, but should not dominate legal risk.

### 5.3 Owner Linkage Policy

If either of the following fails:

1. `plots.owner_nida == submitted_nin`
2. Latest `plot_ownership_histories.to_nida == submitted_nin` (when history exists)

Then verdict is direct `DO_NOT_BUY` without continuing to normal scoring.

Suggested forced values for consistency:

- `risk_score = 95`
- reason includes ownership mismatch details.

## 6. Layer B: Deterministic Risk Scoring Engine

## 6.1 Design Principles

1. Same input data must always produce the same score.
2. Every point added must be traceable to a concrete factor.
3. Higher score means higher legal/financial transaction risk.
4. Final score is clamped to `0..100`.

## 6.2 Score Formula

`final_score = min(100, base_sum + interaction_penalties + uncertainty_penalty)`

Where:

- `base_sum` is sum of individual factor points.
- `interaction_penalties` captures dangerous combinations.
- `uncertainty_penalty` is added when critical risk data is missing.

## 6.3 Factor Weights (v1)

| Factor | Condition | Points |
|---|---|---|
| Active bank loan | At least one `plot_encumbrances.status = Active` | `+15` |
| Loan default severity | Any `plot_encumbrances.status = Defaulted` | `+10` |
| Bank loan cap | Cap combined loan points | `max +25` |
| Ongoing legal disputes | 1 ongoing dispute | `+25` |
| Ongoing legal disputes | 2 ongoing disputes | `+32` |
| Ongoing legal disputes | 3 or more ongoing disputes | `+40` |
| Active caveats | 1 active caveat | `+18` |
| Active caveats | 2 or more active caveats | `+28` |
| Double allocation flag | `plots.double_allocation_flag = true` | `+35` |
| Ownership changes | 2 to 3 transfers | `+8` |
| Ownership changes | 4 to 5 transfers | `+15` |
| Ownership changes | 6 or more transfers | `+22` |
| Land rates overdue | Overdue up to 12 months | `+6` |
| Land rates overdue | Overdue > 12 to 24 months | `+12` |
| Land rates overdue | Overdue > 24 months | `+20` |
| No land-rate record | No `plot_land_rates` rows found | `+20` |
| Plot status | `Under Review` | `+15` |
| Plot status | `Disputed` | `+25` |
| Plot status | `Revoked` | `+40` |
| Certificate risk | `certificate_type = Letter of Offer` | `+8` |
| Certificate risk | `expiry_date` within 90 days | `+6` |
| Certificate risk | `expiry_date` already past | `+20` |

Notes:

- If rates are not overdue, rates add `0`.
- If certificate has no expiry and is legally acceptable for type, add `0`.

## 6.4 Interaction Penalties

Add extra penalty when high-risk factors co-exist:

| Combination | Points |
|---|---|
| Double allocation + (ongoing dispute or active caveat) | `+10` |
| Revoked status + expired certificate | `+10` |
| Active/defaulted loan + rates overdue > 24 months | `+5` |

## 6.5 Uncertainty Penalty

If critical fields are missing (status, certificate dates/type, land-rate history, dispute status, caveat status), add:

- `uncertainty_penalty = +8`

This avoids falsely low scores from incomplete records.

## 6.6 Guardrails

To prevent under-scoring severe records:

1. If `double_allocation_flag = true`, enforce `final_score >= 70`.
2. If `status = Revoked`, enforce `final_score >= 70`.

## 6.7 Verdict Mapping

| Risk Score | Verdict |
|---|---|
| 0 to 29 | `SAFE` |
| 30 to 69 | `CAUTION` |
| 70 to 100 | `DO_NOT_BUY` |

## 6.8 Data Source Mapping

| Required Data | Source |
|---|---|
| Plot status, certificate fields, double allocation | `plots` |
| Active/defaulted loans | `plot_encumbrances` |
| Ongoing disputes | `plot_disputes` |
| Active caveats | `plot_caveats` |
| Land-rate recency | `plot_land_rates` |
| Ownership transfer count | `plot_ownership_histories` |

## 7. Gemini Integration Design

## 7.1 Role of Gemini

Gemini provides:

1. Final buyer-friendly explanation.
2. Contextual recommendation steps.
3. Readable summary of why the verdict was reached.

Gemini does not replace deterministic scoring in v1.

## 7.2 Input Payload to Gemini

Gemini request must include:

1. `system_risk_score` (deterministic score).
2. `system_verdict` (from thresholds).
3. `risk_breakdown` (factor-by-factor points and reasons).
4. Full raw land data (`Plot::getAiPayload()` or equivalent normalized payload).
5. Gate context (GPS pass/fail state, NIDA pass state, owner linkage result).

## 7.3 Prompt Requirements

Prompt must:

1. Position model as Tanzanian land verification expert.
2. Instruct model to respect deterministic score and policy.
3. Instruct model to avoid making up facts.
4. Instruct model to return strict JSON only.

## 7.4 Strict JSON Response Contract

Gemini must return valid JSON with exactly these keys:

```json
{
  "verdict": "SAFE|CAUTION|DO_NOT_BUY",
  "risk_score": 0,
  "reasons": ["string"],
  "recommendation": "string"
}
```

Validation rules:

1. `verdict` must be one of `SAFE`, `CAUTION`, `DO_NOT_BUY`.
2. `risk_score` must be integer in `0..100`.
3. `reasons` must be non-empty array of strings.
4. `recommendation` must be non-empty string.

v1 deterministic lock:

- Gemini `risk_score` must equal `system_risk_score`.
- If Gemini verdict conflicts with deterministic threshold, system overrides with deterministic verdict.

## 7.5 Fallback Strategy

If Gemini call fails, times out, returns non-JSON, or fails schema validation:

1. Keep deterministic verdict and score.
2. Generate deterministic reasons from factor breakdown.
3. Use deterministic recommendation template.
4. Mark payload with fallback flag for audit.

No verification should fail only because Gemini is unavailable.

## 8. Logging and Audit Requirements

`verification_logs` should store:

- `ai_verdict` (final verdict used by system)
- `risk_score` (deterministic score)
- `ai_reasons`
- `ai_recommendation`
- `ai_payload` containing trace data

Recommended `ai_payload` structure:

```json
{
  "version": "risk-v1",
  "verification_context": {
    "gps_passed": true,
    "nida_passed": true,
    "owner_link_passed": true
  },
  "risk_engine": {
    "score": 62,
    "verdict": "CAUTION",
    "factors": [
      {"name": "ongoing_disputes", "points": 25, "detail": "1 ongoing dispute"}
    ],
    "interaction_penalties": [
      {"name": "double_allocation_plus_dispute", "points": 10}
    ],
    "uncertainty_penalty": 0
  },
  "gemini": {
    "model": "configured-model-name",
    "prompt_version": "v1",
    "raw_response": "{...}",
    "parsed_ok": true,
    "fallback_used": false,
    "validation_errors": []
  }
}
```

## 9. Response Policy for Client Applications

Recommended final response shape after step 4 success:

```json
{
  "assessment": {
    "verdict": "CAUTION",
    "risk_score": 62,
    "reasons": [
      "There is one ongoing legal dispute on this plot."
    ],
    "recommendation": "Request certified dispute records before payment."
  }
}
```

Gate failure response rules:

1. GPS/NIDA issues return incomplete-style error and retry guidance.
2. Owner-link mismatch returns do-not-buy style response with legal warning.

## 10. Example Decision Scenarios

## 10.1 Scenario A: GPS mismatch only

- GPS check fails once.
- NIDA not yet completed.

Expected:

- Verdict not finalized.
- Return retry guidance.
- No direct `DO_NOT_BUY`.

## 10.2 Scenario B: Owner mismatch

- GPS passed.
- NIDA challenge passed.
- Submitted NIN does not match plot legal owner chain.

Expected:

- Direct `DO_NOT_BUY`.
- Forced high risk score (for example 95).
- Gemini optional for explanation, not for decision change.

## 10.3 Scenario C: Legal and financial risk pileup

- 1 active dispute, 1 active caveat, rates overdue 18 months.
- No owner mismatch.

Expected:

- Deterministic score in `CAUTION` or `DO_NOT_BUY` band depending totals.
- Gemini explains risks and next due-diligence actions.

## 11. Acceptance Criteria for Implementation

1. Deterministic engine returns same score for same data every time.
2. GPS/NIDA failures do not auto-produce `DO_NOT_BUY`.
3. Owner linkage failure always produces direct `DO_NOT_BUY`.
4. Gemini output is validated against strict schema.
5. System falls back safely when Gemini fails.
6. Logs capture enough detail to explain every decision.

## 12. Rollout Plan

1. Phase 1: Enable deterministic scoring + logging.
2. Phase 2: Enable Gemini explanation with strict fallback.
3. Phase 3: Tune weights using production audit data.

Weight tuning should be versioned (for example `risk-v1`, `risk-v2`) to preserve audit traceability.
