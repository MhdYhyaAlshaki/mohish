# Laravel Backend — Technical Reference

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Authentication](#authentication)
3. [Error Contract](#error-contract)
4. [API Endpoints](#api-endpoints)
   - [Public Endpoints](#public-endpoints)
   - [Authenticated Endpoints](#authenticated-endpoints)
   - [Ad Placement Engine (v1)](#ad-placement-engine-v1)
5. [Service Layer](#service-layer)
6. [Database Schema](#database-schema)
7. [Configuration & Settings](#configuration--settings)

---

## Architecture Overview

```
HTTP Request
     │
     ▼
routes/api.php
     │
     ├── [public]  No middleware
     │
     └── [protected]  token.auth middleware (TokenAuth)
                │
                ▼
         Controller  (thin, delegates immediately)
                │
                ▼
         Service Layer  (all business logic lives here)
                │
                ├── RewardService      — rewarded ads, claims, withdrawals
                ├── AdsDecisionEngine  — campaign selection & scoring
                ├── SettingsService    — dynamic config from DB
                ├── FraudSignalService — risk scoring for ad sessions
                └── VpnDetectionService — VPN check for ad requests
                │
                ▼
         Eloquent Models / DB (transactions use lockForUpdate)
```

- Controllers extend `BaseApiController` for a uniform JSON response shape.
- Validation errors for `api/*` routes are globally normalized in `bootstrap/app.php`.
- Reward-sensitive operations (`completeAdSession`, `claimReward`, `requestWithdraw`) run inside `DB::transaction()` with `lockForUpdate()` to prevent race conditions.
- Dynamic knobs (daily cap, cooldown, points per ad, etc.) are stored in the `system_settings` table and read through `SettingsService`, with `config/reward.php` as the fallback.

---

## Authentication

All authenticated routes require a **Bearer token** sent in one of these headers:

```
Authorization: Bearer <api_token>
X-API-TOKEN: <api_token>
```

The `api_token` is a 60-character random string generated on `register` and rotated on every `login`. It is stored in the `users.api_token` column.

**Middleware**: `App\Http\Middleware\TokenAuth` (`token.auth` alias)

| Scenario | HTTP Status | Response |
|----------|-------------|----------|
| Header missing | `401` | `{"code":"unauthenticated","message":"Missing API token."}` |
| Token not found in DB | `401` | `{"code":"unauthenticated","message":"Invalid API token."}` |

---

## Error Contract

Every error response follows this shape:

```json
{
  "code": "snake_case_error_code",
  "message": "Human-readable explanation.",
  "retry_after": 42
}
```

`retry_after` (seconds) is only present when the client should back off (e.g., cooldown or rate-limit errors).

Validation errors (HTTP `422`):

```json
{
  "code": "validation_error",
  "message": "The first validation error message."
}
```

---

## API Endpoints

### Public Endpoints

#### `POST /api/register`

Creates a new user account.

**Request Body**

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| `name` | string | Yes | max 120 chars |
| `email` | string | Yes | valid email, max 190 chars, unique |
| `password` | string | Yes | min 6 chars |
| `referral_code` | string | No | max 12 chars; must match an existing user's `referral_code` |

**Success Response** `201 Created`

```json
{
  "token": "<60-char api_token>",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "points": 0,
    "referral_code": "ABCD1234"
  }
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `validation_error` | `422` | Field failed validation |

---

#### `POST /api/login`

Authenticates an existing user. Rotates the `api_token` on every successful login.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `email` | string | Yes |
| `password` | string | Yes |

**Success Response** `200 OK`

```json
{
  "token": "<60-char api_token>",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "points": 120,
    "referral_code": "ABCD1234"
  }
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `invalid_credentials` | `401` | Email not found or password mismatch |
| `validation_error` | `422` | Field failed validation |

---

#### `GET /api/v1/config`

Returns all server-driven configuration the mobile app needs before it can initialize ads. Intentionally public so it can be called before login (app cold-start).

**Query Parameters**

| Param | Type | Default | Values |
|-------|------|---------|--------|
| `platform` | string | `android` | `android`, `ios` |

**Success Response** `200 OK`

```json
{
  "ads_enabled": true,
  "sdk_init": {
    "admob_app_id_android": "ca-app-pub-...",
    "admob_app_id_ios": "ca-app-pub-..."
  },
  "feature_flags": {
    "rewarded_ads": true,
    "interstitial_ads": true,
    "app_open_ads": true
  },
  "fallback_placements": {
    "home_rewarded": {
      "network": "admob",
      "android_ad_unit_id": "ca-app-pub-...",
      "ios_ad_unit_id": "ca-app-pub-..."
    },
    "splash_interstitial": {
      "network": "admob",
      "android_ad_unit_id": "ca-app-pub-...",
      "ios_ad_unit_id": "ca-app-pub-..."
    },
    "app_open": {
      "network": "admob",
      "android_ad_unit_id": "ca-app-pub-...",
      "ios_ad_unit_id": "ca-app-pub-..."
    }
  }
}
```

All values are served from `system_settings` with hardcoded AdMob test IDs as fallbacks.

---

#### `GET /api/v1/ad`

Resolves the best ad campaign for a placement. Logs one `ad_impressions` row on every successful resolve and increments `campaigns.spent`.

**Query Parameters**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `placement` | string | Yes | Placement key (e.g. `home_rewarded`, `splash_interstitial`) |
| `platform` | string | Yes | `android` \| `ios` \| `web` |
| `country` | string | No | ISO 3166-1 alpha-2 (e.g. `AE`, `US`) |

**Scoring Formula**

```
score = (campaign.priority × 1 000) + (network_cpm_for_platform × 500)
```

Eligibility filters applied before scoring:
1. Placement must be active.
2. Campaign must be active and within its date window.
3. Campaign must be within its budget.
4. Campaign's `target_platforms` must include the request platform.
5. Campaign's `target_countries` must include the request country (if set).
6. The ad network must be active.
7. VPN traffic is blocked unless the campaign's `ad_config.allow_vpn` is `true`.

**Success Response** `200 OK`

```json
{
  "impression_id": 42,
  "type": "rewarded",
  "network": "admob",
  "ad_unit_id": "ca-app-pub-...",
  "placement_key": "home_rewarded",
  "refresh_after": 30,
  "click_url": null,
  "platform": "android",
  "blocked": false
}
```

**Responses when no ad is available**

If a placement-level fallback is configured:
- Returns the fallback response with `"impression_id": null`.

If no fallback either:
- `404` `{"blocked":false,"ad_unit_id":null,"reason":"No active campaign for this placement"}`

**VPN blocked response** `403`

```json
{
  "blocked": true,
  "reason": "VPN detected"
}
```

---

#### `POST /api/v1/ad/click`

Records that the user clicked the ad. Idempotent — only one click row is created per impression (unique constraint on `ad_impression_id`).

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `impression_id` | integer | Yes — must exist in `ad_impressions` |

**Success Response** `200 OK`

```json
{ "ok": true }
```

---

### Authenticated Endpoints

All endpoints below require `Authorization: Bearer <token>`.

---

#### `GET /api/profile`

Returns the authenticated user's profile.

**Success Response** `200 OK`

```json
{
  "id": 1,
  "name": "Jane Doe",
  "email": "jane@example.com",
  "points": 350,
  "referral_code": "ABCD1234",
  "referred_by": 5
}
```

`referred_by` is the `id` of the user who referred this account, or `null`.

---

#### `GET /api/dashboard`

Returns the user's stats summary for display on the home screen.

**Success Response** `200 OK`

```json
{
  "total_points": 350,
  "today_points": 40,
  "ads_watched_today": 4,
  "weekly_points": [10, 0, 30, 50, 20, 40, 10]
}
```

`weekly_points` is a 7-element array ordered from 6 days ago (index 0) to today (index 6). Only positive transaction points are counted (withdrawals are excluded).

---

#### `POST /api/ad/start`

Starts a rewarded-ad session. Checks the daily cap and cooldown before issuing a `session_id`. Also runs fraud scoring.

**Request Body**

| Field | Type | Required | Default |
|-------|------|----------|---------|
| `ad_type` | string | No | `rewarded` |
| `device_fingerprint` | string | No | — |
| `vpn_flag` | boolean | No | `false` |

**Success Response** `200 OK`

```json
{
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "expires_at": "2026-04-30T12:15:00+00:00",
  "cooldown_seconds": 30,
  "remaining_today": 8
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `daily_limit_reached` | `429` | User has hit the daily ad cap |
| `cooldown_active` | `429` | Must wait; `retry_after` gives seconds remaining |

---

#### `POST /api/ad/complete`

Marks a rewarded-ad session as completed and awards points. Uses a DB transaction with `lockForUpdate` on both the session and user rows. Idempotent: re-submitting a completed session returns the original award without re-crediting.

Also triggers referral bonus crediting if the user was referred.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `session_id` | UUID | Yes |

**Success Response** `200 OK`

```json
{
  "awarded_points": 10,
  "new_balance": 360,
  "daily_count": 5,
  "cooldown_seconds": 30,
  "next_available_at": "2026-04-30T12:15:30+00:00"
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `session_not_found` | `404` | `session_id` does not belong to this user |
| `session_expired` | `422` | Session window elapsed; start a new one |

---

#### `POST /api/claim-reward`

Awards the daily bonus points. Limited to one successful claim per calendar day.

**Request Body** — none

**Success Response** `200 OK`

```json
{
  "claimed_points": 5,
  "new_balance": 365
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `reward_already_claimed` | `429` | Daily claim already used today |

---

#### `POST /api/withdraw`

Requests a withdrawal. Deducts points immediately and creates a `withdrawals` record with `status = pending`. Admin processes the payout manually.

**Request Body**

| Field | Type | Required | Constraints |
|-------|------|----------|-------------|
| `points` | integer | Yes | min `1` (server checks against `min_withdraw_points` setting) |

**Success Response** `200 OK`

```json
{
  "withdrawal_id": 12,
  "status": "pending",
  "new_balance": 0
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `below_minimum_withdraw` | `422` | `points` is below the configured minimum |
| `insufficient_balance` | `422` | User's current balance is less than requested `points` |

---

#### `GET /api/referrals`

Returns the authenticated user's referral code, total referral earnings, and list of referred accounts.

**Success Response** `200 OK`

```json
{
  "referral_code": "ABCD1234",
  "total_referral_earnings": 85,
  "items": [
    {
      "id": 3,
      "earnings": 50,
      "referred_user": {
        "id": 7,
        "name": "Bob",
        "email": "bob@example.com"
      }
    }
  ]
}
```

---

#### `POST /api/apply-code`

Links this account to a referrer. Can only be done once; locked after the first successful application.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `code` | string | Yes — max 12 chars |

**Success Response** `200 OK`

```json
{
  "message": "Referral code applied successfully.",
  "referred_by": 5
}
```

**Error Codes**

| Code | HTTP | Meaning |
|------|------|---------|
| `referral_locked` | `422` | A referral code was already applied to this account |
| `invalid_referral_code` | `422` | Code not found or is the user's own code |

---

### Ad Placement Engine (v1)

All `v1` ad-engine endpoints also require the Bearer token.

---

#### `POST /api/v1/ad/impression`

Called when an ad actually renders on screen (confirm-render event). Supplements the impression row created by `GET /api/v1/ad`. Sets `confirmed_at` on the impression row.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `impression_id` | integer | Yes — must exist in `ad_impressions` |
| `placement` | string | No |

**Success Response** `200 OK`

```json
{ "ok": true }
```

---

#### `POST /api/v1/ad/reward`

Lightweight analytics tracking for when the user earns a reward from a rewarded ad. Sets `rewarded_at` on the impression row. Point awarding still happens via `POST /api/ad/complete`.

**Request Body**

| Field | Type | Required |
|-------|------|----------|
| `impression_id` | integer | Yes — must exist in `ad_impressions` |
| `placement` | string | No |

**Success Response** `200 OK`

```json
{ "ok": true }
```

---

#### `GET /api/v1/ad/stats`

Returns aggregated impression, revenue, CPM, and CTR stats per platform. Intended for admin/analytics use.

**Query Parameters**

| Param | Type | Default |
|-------|------|---------|
| `days` | integer | `7` |

**Success Response** `200 OK`

```json
{
  "days": 7,
  "stats": [
    {
      "platform": "ios",
      "impressions": 1500,
      "estimated_revenue": "2.250000",
      "avg_cpm": "1.500000",
      "vpn_impressions": 12,
      "clicks": 45,
      "ctr": 3.00
    },
    {
      "platform": "android",
      "impressions": 3200,
      "estimated_revenue": "1.600000",
      "avg_cpm": "0.500000",
      "vpn_impressions": 30,
      "clicks": 64,
      "ctr": 2.00
    }
  ]
}
```

---

## Service Layer

### `RewardService`

Central service for all user-balance operations.

| Method | Description |
|--------|-------------|
| `startAdSession(User, array $context)` | Checks daily cap + cooldown, creates `ads_logs` row with risk score, returns `session_id`. |
| `completeAdSession(User, string $sessionId)` | DB transaction: marks session completed, awards points (base × payout multiplier), creates `transactions` row, triggers referral earnings. |
| `claimReward(User)` | DB transaction: awards daily claim points once per day. |
| `requestWithdraw(User, int $points)` | DB transaction: deducts points, creates `withdrawals` + `transactions` row. |

**Points per ad formula:**

```
points = round(base_user_payout_points_per_ad × payout_tier.payout_multiplier)
         capped at minimum 1
```

**Referral bonus (applied inside `completeAdSession`):**

```
bonus = floor(awarded_points × referral_percent / 100)
```

The bonus is credited to the referrer's balance and recorded in `referrals.earnings` and a `transactions` row of type `referral`.

---

### `AdsDecisionEngine`

Selects the best campaign for a placement request.

**Scoring formula:**

```
score = (campaign.priority × 1 000) + (network_cpm_for_platform × 500)
```

Eligibility filters (all must pass):
1. Placement is active.
2. Campaign is active and within its `start_date`/`end_date` window.
3. Campaign `spent` < `budget` (or budget is null).
4. `target_platforms` includes the request platform (or is null).
5. `target_countries` includes the request country (or is null).
6. The campaign's ad network is active.
7. VPN traffic is blocked unless `ad_config.allow_vpn` is `true`.

If no campaign is eligible and the placement has a `fallback_config`, the fallback `ad_unit_id` is returned with `impression_id: null`.

---

### `FraudSignalService`

Produces a `risk_score` (0–100) stored on every `ads_logs` row.

| Signal | Score added |
|--------|-------------|
| VPN flag is `true` | +40 |
| Device fingerprint shared by >1 user in the last 24 h | +10 per extra user, capped at +50 |

Total is capped at 100.

---

### `SettingsService`

Reads dynamic configuration from the `system_settings` table with in-request caching.

| Method | Returns |
|--------|---------|
| `getString(key, default)` | `?string` |
| `getBool(key, default)` | `bool` |
| `getInt(key, default)` | `int` |
| `getFloat(key, default)` | `float` |
| `set(key, value, group, updatedBy)` | Persists or updates a setting and clears cache |

---

## Database Schema

### `users`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string(120) | |
| `email` | string(190) | unique |
| `password` | string | bcrypt-hashed |
| `points` | integer | current balance |
| `api_token` | string(60) | bearer token; hidden from JSON |
| `referral_code` | string(8) | uppercase, unique, auto-generated |
| `referred_by` | bigint FK → users | nullable |
| `payout_tier_id` | bigint FK → payout_tiers | nullable |
| `admin_role` | string | `admin` or `analyst` (Filament access) |
| `is_flagged` | boolean | fraud flag |

---

### `transactions`

Immutable ledger of all point changes.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK | |
| `type` | string(32) | `ad`, `claim_reward`, `referral`, `withdrawal` |
| `points` | integer | positive = credit, negative = debit |
| `status` | string(24) | `completed` or `pending` |
| `meta` | json | nullable; e.g. `{"session_id":"..."}` |

---

### `ads_logs`

One row per rewarded-ad session attempt.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK | |
| `ad_type` | string(24) | default `rewarded` |
| `session_id` | uuid | unique |
| `completed` | boolean | |
| `reward_given` | integer | points awarded |
| `started_at` | timestamp | |
| `completed_at` | timestamp | nullable |
| `expires_at` | timestamp | nullable |
| `ip_address` | string(45) | nullable |
| `user_agent` | text | nullable |
| `device_fingerprint` | string(120) | nullable |
| `vpn_flag` | boolean | |
| `risk_score` | tinyint (0–100) | from `FraudSignalService` |

---

### `withdrawals`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK | |
| `points` | unsigned bigint | amount requested |
| `status` | string(24) | `pending` → manually updated by admin |
| `note` | text | nullable; admin note |

---

### `referrals`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK | the referrer |
| `referred_user_id` | bigint FK | the referred account |
| `earnings` | integer | cumulative bonus points earned from this referral |

Unique on `(user_id, referred_user_id)`.

---

### Ad Management Tables

#### `ad_networks`

| Column | Type | Notes |
|--------|------|-------|
| `slug` | string | `admob`, `meta`, `unity`, `applovin`, `direct` |
| `android_cpm_estimate` | decimal(8,4) | used in scoring |
| `ios_cpm_estimate` | decimal(8,4) | typically 1.5–3× Android |
| `base_priority` | integer | |
| `credentials` | json | encrypted; never exposed via API |

#### `ad_placements`

| Column | Type | Notes |
|--------|------|-------|
| `key` | string | unique; e.g. `home_rewarded`, `splash_interstitial`, `app_open` |
| `ad_type_id` | FK → ad_types | |
| `fallback_config` | json | fallback `ad_unit_id` if no campaign matches |

#### `campaigns`

| Column | Type | Notes |
|--------|------|-------|
| `ad_network_id` | FK | |
| `ad_placement_id` | FK | |
| `budget` | decimal(12,2) | nullable = unlimited |
| `spent` | decimal(12,6) | incremented atomically on each impression |
| `priority` | integer | affects scoring |
| `target_platforms` | json | `["android","ios"]` or `null` = all |
| `target_countries` | json | `["AE","US"]` or `null` = all |
| `ad_config` | json | `android_ad_unit_id`, `ios_ad_unit_id`, `allow_vpn`, `click_url`, `refresh_after` |
| `start_date` / `end_date` | date | nullable |

#### `ad_impressions`

One row per ad served by the decision engine.

| Column | Type | Notes |
|--------|------|-------|
| `campaign_id` | FK | |
| `user_id` | FK | nullable |
| `placement_key` | string(100) | |
| `platform` | string(20) | |
| `country_code` | string(5) | nullable |
| `is_vpn` | boolean | |
| `estimated_revenue` | decimal(10,6) | `cpm / 1000` at serve time |
| `confirmed_at` | timestamp | set by `POST /api/v1/ad/impression` |
| `rewarded_at` | timestamp | set by `POST /api/v1/ad/reward` |

#### `ad_clicks`

One row per click; unique on `ad_impression_id`.

---

### `payout_tiers`

| Column | Type | Notes |
|--------|------|-------|
| `name` | string(24) | e.g. `regular`, `premium` |
| `payout_multiplier` | decimal(8,4) | multiplied against `base_user_payout_points_per_ad` |

---

### `system_settings`

| Column | Type | Notes |
|--------|------|-------|
| `key` | string | unique |
| `group` | string | e.g. `general`, `reward` |
| `value` | string | stored as string; cast by `SettingsService` |
| `value_type` | string | `string`, `int`, `float`, `bool` |
| `updated_by` | FK → users | nullable |

---

## Configuration & Settings

### `config/reward.php` (fallback defaults)

| Key | Env var | Default |
|-----|---------|---------|
| `daily_cap` | `REWARD_DAILY_CAP` | `12` |
| `cooldown_seconds` | `REWARD_COOLDOWN_SECONDS` | `30` |
| `points_per_ad` | `REWARD_POINTS_PER_AD` | `10` |
| `referral_percent` | `REWARD_REFERRAL_PERCENT` | `10` |
| `session_expiry_minutes` | `REWARD_SESSION_EXPIRY_MINUTES` | `10` |
| `daily_claim_points` | `REWARD_DAILY_CLAIM_POINTS` | `5` |
| `min_withdraw_points` | `REWARD_MIN_WITHDRAW_POINTS` | `1000` |

These values are overridden at runtime by any matching row in `system_settings` (managed via the Filament admin panel).

### Dynamic Settings Keys (via `SettingsService`)

| Key | Type | Description |
|-----|------|-------------|
| `daily_cap` | int | Max ads a user can complete per day |
| `cooldown_seconds` | int | Minimum seconds between completed ads |
| `session_expiry_minutes` | int | How long a started session stays valid |
| `base_user_payout_points_per_ad` | float | Base points before tier multiplier |
| `referral_percent` | int | % of earned points credited to referrer |
| `daily_claim_points` | int | Points awarded per daily claim |
| `min_withdraw_points` | int | Minimum points required to withdraw |
| `ads_enabled` | bool | Global ad kill-switch |
| `feature_flag_rewarded_ads` | bool | Enable/disable rewarded ad type |
| `feature_flag_interstitial_ads` | bool | Enable/disable interstitial ad type |
| `feature_flag_app_open_ads` | bool | Enable/disable app-open ad type |
| `admob_app_id_android` | string | AdMob app ID for Android |
| `admob_app_id_ios` | string | AdMob app ID for iOS |
| `fallback_home_rewarded_android` | string | Fallback ad unit ID |
| `fallback_home_rewarded_ios` | string | Fallback ad unit ID |
| `fallback_splash_interstitial_android` | string | Fallback ad unit ID |
| `fallback_splash_interstitial_ios` | string | Fallback ad unit ID |
| `fallback_app_open_android` | string | Fallback ad unit ID |
| `fallback_app_open_ios` | string | Fallback ad unit ID |

### Developer Commands

```bash
# From backend/
composer run setup   # Full bootstrap (migrate, seed)
composer run dev     # Local dev stack
composer test        # PHPUnit (in-memory SQLite)
```
