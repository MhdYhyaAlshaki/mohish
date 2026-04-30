# Flutter ↔ Laravel Integration Guide

This document explains how the Flutter app (`/lib`) talks to the Laravel backend (`/backend`) and how data flows through auth, rewards, ads, wallet, referrals, and analytics.

## 1) High-Level Architecture

- Flutter app uses **feature-based structure + Cubits**.
- All network calls go through a single shared `ApiClient` (Dio-based).
- Laravel exposes:
  - public startup APIs (`/api/v1/config`, `/api/v1/ad`)
  - authenticated user/reward APIs (`/api/profile`, `/api/ad/start`, etc.)
- Auth uses bearer token (`Authorization: Bearer <token>`) from `users.api_token`.

## 2) Flutter Boot Sequence

Entry file: [main.dart](/Users/mohammad/Documents/New%20project/lib/main.dart)

Startup order:
1. Create shared `ApiClient`.
2. Load remote config from backend (`GET /api/v1/config?platform=...`).
3. Create ad placement service (`GET /api/v1/ad?...` + tracking endpoints).
4. Initialize Google Mobile Ads SDK.
5. Preload ad services (rewarded/interstitial/app-open).
6. Launch `MohishApp` with shared services.

App DI container: [mohish_app.dart](/Users/mohammad/Documents/New%20project/lib/app/mohish_app.dart)

## 3) Network Layer

Client implementation: [api_client.dart](/Users/mohammad/Documents/New%20project/lib/core/data/api_client.dart)

- Uses `Dio` with base URL from `API_BASE_URL` dart define.
- Converts backend errors into `ApiException { code, message, retryAfter }`.
- Token is set/cleared by `setToken()`.

## 4) Authentication Flow

Flutter:
- Repository: [auth_repository.dart](/Users/mohammad/Documents/New%20project/lib/features/auth/data/auth_repository.dart)
- State: [auth_cubit.dart](/Users/mohammad/Documents/New%20project/lib/features/auth/presentation/auth_cubit.dart)

Backend:
- Routes: [api.php](/Users/mohammad/Documents/New%20project/backend/routes/api.php)
- Controller: [AuthController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/AuthController.php)
- Middleware: [TokenAuth.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Middleware/TokenAuth.php)

Flow:
1. `POST /api/register` or `POST /api/login`
2. Backend returns `token` + `user`
3. Flutter stores token in `SharedPreferences` via `AuthStorage`
4. On app restart, `AuthCubit.bootstrap()` restores token and loads `/profile`

## 5) Feature ↔ Endpoint Mapping

### Dashboard
- Flutter: [dashboard_repository.dart](/Users/mohammad/Documents/New%20project/lib/features/dashboard/data/dashboard_repository.dart)
- Endpoint: `GET /api/dashboard`
- Backend: [UserController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/UserController.php)

### Wallet
- Flutter: [wallet_repository.dart](/Users/mohammad/Documents/New%20project/lib/features/wallet/data/wallet_repository.dart)
- Endpoints:
  - `GET /api/profile`
  - `POST /api/claim-reward`
  - `POST /api/withdraw`
- Backend: [RewardController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/RewardController.php)

### Referrals
- Flutter: [referral_repository.dart](/Users/mohammad/Documents/New%20project/lib/features/referral/data/referral_repository.dart)
- Endpoints:
  - `GET /api/referrals`
  - `POST /api/apply-code`
- Backend: [ReferralController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/ReferralController.php)

### Legacy Rewarded Earn Loop (points credit)
- Flutter: [ads_repository.dart](/Users/mohammad/Documents/New%20project/lib/features/ads/data/ads_repository.dart), [ads_cubit.dart](/Users/mohammad/Documents/New%20project/lib/features/ads/presentation/ads_cubit.dart)
- Endpoints:
  - `POST /api/ad/start`
  - `POST /api/ad/complete`
- Backend: [AdController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/AdController.php), [RewardService.php](/Users/mohammad/Documents/New%20project/backend/app/Services/RewardService.php)

## 6) New Ad Placement Engine (v1)

Flutter services:
- [remote_config_service.dart](/Users/mohammad/Documents/New%20project/lib/core/data/remote_config_service.dart)
- [ad_placement_service.dart](/Users/mohammad/Documents/New%20project/lib/core/data/ad_placement_service.dart)
- [rewarded_ad_service.dart](/Users/mohammad/Documents/New%20project/lib/core/data/rewarded_ad_service.dart)

Backend:
- [ConfigController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/ConfigController.php)
- [AdPlacementController.php](/Users/mohammad/Documents/New%20project/backend/app/Http/Controllers/Api/AdPlacementController.php)

Flow:
1. `GET /api/v1/config` provides:
   - global `ads_enabled`
   - feature flags
   - fallback placements per platform/network
2. `GET /api/v1/ad?placement=...&platform=...` selects best campaign.
3. Flutter tracks:
   - `POST /api/v1/ad/impression`
   - `POST /api/v1/ad/reward`
   - `POST /api/v1/ad/click`

## 7) Profit & Admin Analytics (Backend)

Admin panel: `/admin` (Filament)

Core analytics/profit services:
- [AnalyticsService.php](/Users/mohammad/Documents/New%20project/backend/app/Services/AnalyticsService.php)
- [ProfitCalculator.php](/Users/mohammad/Documents/New%20project/backend/app/Services/ProfitCalculator.php)
- [UserScoreCalculator.php](/Users/mohammad/Documents/New%20project/backend/app/Services/UserScoreCalculator.php)

Metric snapshot command:
- [ComputeDailyUserMetrics.php](/Users/mohammad/Documents/New%20project/backend/app/Console/Commands/ComputeDailyUserMetrics.php)
- Scheduled in [console.php](/Users/mohammad/Documents/New%20project/backend/routes/console.php)

Admin analytics endpoints:
- `GET /admin/analytics/overview`
- `GET /admin/analytics/top-users`
- `GET /admin/analytics/profit-trend`

## 8) Required Environment / Runtime Notes

- Flutter base URL defaults to `http://127.0.0.1:8000/api`.
  - iOS simulator can use `127.0.0.1`.
  - Android emulator usually needs `10.0.2.2`.
- iOS + Google Mobile Ads requires `GADApplicationIdentifier` in `ios/Runner/Info.plist`.
- Backend must be migrated and seeded before app login/testing:
  - `php artisan migrate:fresh --seed`

## 9) Quick End-to-End Local Test

1. Run Laravel:
   - `cd backend && php artisan serve`
2. Run Flutter:
   - `flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`
3. Register user in app.
4. Verify:
   - Dashboard data loads
   - Watch & earn triggers `/ad/start` then `/ad/complete`
   - Wallet claim/withdraw works
   - Referral code apply/list works

