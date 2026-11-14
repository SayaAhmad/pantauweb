# Specification: Google OAuth Authentication via Laravel Socialite

## 1. Overview
Implement user authentication using Google OAuth 2.0 through Laravel Socialite, enabling:
- Login with Google account
- Automatic account linking (auto-link) for existing users by email
- New user registration when email not found
- Password field made nullable for OAuth-only accounts
- Secure session handling and error management

## 2. Goals
- Provide seamless Google login experience for users
- Maintain data isolation (users only access their own resources)
- Follow Laravel best practices for authentication flows
- Ensure data integrity and security

## 3. Scope
### In Scope
- Google OAuth 2.0 authentication flow (redirect → callback)
- Auto-linking of existing user accounts by email
- New user registration for unregistered emails
- Password field made nullable in `users` table
- Configuration via `.env` and `config/services.php`
- Middleware protection for guest users
- Session management and callback handling
- Documentation in `docs/intent` and `docs/adr`

### Out of Scope
- Support for additional OAuth providers (GitHub, Facebook, etc.)
- Avatar/profile picture synchronization from Google
- Socialite driver configuration for multiple providers simultaneously
- Frontend UI components (buttons, forms) - will be handled separately

## 4. Assumptions
- User email from Google is unique and matches existing email format
- User email is the primary identifier for account linking
- Email verification status is handled separately (not part of this scope)
- Google provider credentials are already configured in environment

## 5. Functional Requirements
1. **Login Flow**:
   - User clicks "Login with Google" button on login page
   - Redirect to Google OAuth provider
   - After successful authentication, Google redirects back to application callback URL
   - Application processes callback, authenticates user, creates session

2. **Auto-Link Logic**:
   - If email from Google matches existing user email:
     - Set `google_id` on existing user
     - Ensure `password` is nullable (no password required)
     - Log user in automatically
   - If no matching email:
     - Create new user record with:
       - `google_id` (unique)
       - `email` (from Google)
       - `password` = NULL
       - `is_admin` = false (default)
     - Log user in automatically

3. **Registration Flow** (optional enhancement):
   - "Register with Google" button on register page
   - Same auto-link logic applies
   - New user record created if email not found

4. **Security Requirements**:
   - All OAuth communication over HTTPS
   - CSRF protection on callback route
   - Rate limiting on login attempts
   - Password field remains nullable only for OAuth accounts

## 6. Non-Functional Requirements
- **Performance**: Minimal additional latency during authentication flow
- **Security**: Follow Laravel security best practices for OAuth implementation
- **Maintainability**: Clear separation of concerns with service layer
- **Scalability**: Support multiple Google accounts per user (future enhancement)

## 7. Technical Design

### 7.1 Database Schema Changes
- **Migration**: Add `google_id` column to `users` table
  - Type: `string` (nullable)
  - Unique index on `google_id` (allow NULL but unique when present)
  - Migration should allow existing users to have NULL google_id

### 7.2 Model Changes
- **User Model**:
  - Add `google_id` attribute (fillable: false)
  - Ensure `password` remains nullable (already nullable in current schema)
  - Add relationship method if needed (e.g., `googleAccount()`)

### 7.3 Routing
- **web.php**:
  ```php
  // Login with Google
  Route::get('auth/google', [AuthController::class, 'redirectToProvider'])
      ->name('auth.google');
  
  Route::get('auth/google/callback', [AuthController::class, 'handleProviderCallback'])
      ->name('auth.google.callback')
      ->middleware('auth');
  ```

### 7.4 Controllers
- **AuthController**:
  - `redirectToProvider()`: Initiates Google OAuth redirect
  - `handleProviderCallback()`: Handles callback, processes user authentication
  - `logout()`: Optional logout method

### 7.5 Service Layer
- **GoogleAuthService** (optional):
  - Encapsulates OAuth logic
  - Methods: `loginWithGoogle()`, `createUserIfNotExists()`

### 7.6 Configuration
- **config/services.php**:
  ```php
  'google' => [
      'driver' => 'google',
      'client_id' => env('GOOGLE_CLIENT_ID'),
      'client_secret' => env('GOOGLE_CLIENT_SECRET'),
      'redirect' => env('GOOGLE_REDIRECT_URI'),
  ],
  ```

### 7.7 Middleware
- Use existing `auth:guest` middleware for login routes
- `auth` middleware for callback route to ensure authenticated session

### 7.8 Error Handling
- Handle OAuth errors (denied access, invalid state)
- Graceful fallback to manual login if OAuth fails
- Log authentication events for auditing

## 8. Testing Strategy
- **Unit Tests**: Test GoogleAuthService logic
- **Feature Tests**: 
  - Test redirect flow
  - Test callback handling with valid/invalid tokens
  - Test auto-linking behavior
- **Feature Tests**: Verify user creation and login after OAuth callback
- **Security Tests**: Ensure CSRF protection on callback route

## 8.1 Migration Steps
1. Create migration file: `php artisan make:migration add_google_id_to_users_table --table=users`
2. Add `google_id` column with unique index
3. Run migration
4. Update User model with `$fillable` if needed

## 8.2 Implementation Notes
- Use Laravel Socialite's `driver('google')` for OAuth
- Ensure `redirect()->intended()` after successful callback
- Handle `Authenticating` vs `Authenticated` states properly
- Consider adding `google_verified_at` timestamp for future use

## 9. Dependencies
- `laravel/socialite` package (perlu di-install via `composer require laravel/socialite`)
- Google OAuth credentials in `.env` file
- Existing authentication scaffolding (LoginController, RegisterController)

## 10. Acceptance Criteria
- [ ] Google login button functional on login/register pages
- [ ] Existing user with matching email auto-links and logs in
- [ ] New user created when email not found
- [ ] `password` field remains nullable for OAuth accounts
- [ ] `google_id` is stored and unique when present
- [ ] Session maintained properly after authentication
- [ ] Error handling for OAuth failures
- [ ] Documentation updated in `docs/intent` and `docs/adr`