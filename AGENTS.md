# AGENTS Guide

## Repo Shape
- This is a two-part system: Flutter mobile app in `lib/` and Laravel API/admin backend in `backend/`.
- Mobile talks only to backend JSON endpoints under `/api/*` (default base URL: `http://127.0.0.1:8000/api` in `lib/core/data/api_client.dart`).
- Auth is token-based (custom bearer token), not Sanctum/JWT: see `backend/app/Http/Middleware/TokenAuth.php` and `ApiClient.setToken(...)`.

## App Architecture (Flutter)
- Composition root is `lib/app/mohish_app.dart`: shared `ApiClient`, repositories, and Cubits are wired with `MultiRepositoryProvider` + `MultiBlocProvider`.
- Feature layout is consistent: `features/<feature>/{data,domain,presentation}` (example: `features/wallet/*`).
- Repositories are thin endpoint adapters (`DashboardRepository.fetchDashboard`, `WalletRepository.withdraw`, etc.); Cubits own UI state/error handling.
- `ApiException` (`code`, `message`, optional `retryAfter`) is the cross-feature error contract; Cubits surface `exception.message` to UI.
- Rewarded ad flow is orchestrated in `lib/features/ads/presentation/ads_cubit.dart`: `/ad/start` -> local ad SDK (`RewardedAdService`) -> `/ad/complete`.

## Backend Architecture (Laravel)
- API routes are in `backend/routes/api.php`; all authenticated endpoints are inside `Route::middleware('token.auth')`.
- Controllers are intentionally thin and delegate business logic to services (mainly `backend/app/Services/RewardService.php`).
- `BaseApiController` standardizes JSON payloads and error shape (`code`, `message`, `retry_after`).
- Reward operations (`startAdSession`, `completeAdSession`, `claimReward`, `requestWithdraw`) use DB transactions + row locking to keep balances consistent.
- Dynamic reward knobs come from `SettingsService` with config fallback (`backend/config/reward.php`), seeded in `backend/database/seeders/DatabaseSeeder.php`.

## Cross-Component Contracts
- Mobile endpoint paths must match `backend/routes/api.php` exactly (`/profile`, `/dashboard`, `/claim-reward`, `/withdraw`, `/referrals`, `/apply-code`, `/ad/start`, `/ad/complete`).
- Backend validation errors for API requests are normalized in `backend/bootstrap/app.php` to `{"code":"validation_error","message":"..."}`.
- Ads cooldown/daily cap behavior is server-authoritative (`RewardService`) and exposed to mobile via `retry_after`, `remaining_today`, `next_available_at`.

## Developer Workflows
- Flutter (repo root): run `flutter pub get`, `flutter analyze`, `flutter test`, then `flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`.
- Backend (`backend/`): quickest bootstrap is `composer run setup`; local full stack is `composer run dev`; tests run via `composer test`.
- Backend tests use in-memory SQLite (`backend/phpunit.xml`), so feature tests are fast and isolated.

## Project-Specific Conventions
- Keep business rules out of API controllers; extend service layer (follow patterns in `RewardService`).
- When adding API failures, preserve `ApiException`/`BaseApiController` payload format so Flutter error handling continues to work.
- New mobile features should follow existing `data/domain/presentation` split and consume shared `ApiClient` via DI.
- Referral and wallet side effects are ledgered in `transactions` table; maintain idempotency expectations tested in `backend/tests/Feature/AdFlowTest.php`.

