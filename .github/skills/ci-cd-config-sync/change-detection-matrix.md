# CI/CD Config Sync - Change Detection Matrix

Use this matrix to quickly identify what needs updating based on file changes.

## File Change → CI/CD Update Mapping

```
CHANGED FILES                          → REQUIRED CI/CD UPDATE
─────────────────────────────────────────────────────────────────
composer.json                          → Update dependency cache keys
composer.lock                          → Validate lock file in CI
                                         Run: composer audit
                                         
package.json                           → Update Node dependency steps
package-lock.json                      → Validate lock file
pnpm-workspace.yaml                    → Add pnpm install step
                                         
app/Models/*.php                       → Discover new models in tests
app/Http/Controllers/*.php             → Add controller tests
app/Actions/Fortify/*.php              → Run auth tests
app/Http/Requests/*.php                → Run validation tests
app/Policies/*.php                     → Run authorization tests
                                         
database/migrations/*.php              → Run migration verification
                                         Validate fresh migration
database/seeders/*.php                 → Add seed test step
routes/web.php                         → Validate routes exist
                                         
tests/Feature/**/*.php (NEW)           → Add feature test discovery
tests/Unit/**/*.php (NEW)              → Add unit test discovery
tests/Pest.php (MODIFIED)              → Check for new Pest config
                                         
app/Jobs/*.php (NEW)                   → Add queue job testing
app/Console/Commands/*.php (NEW)       → Test Artisan commands
                                         
docker-compose.yml                     → Update service startup
                                         Add service health checks
.env (MODIFIED)                        → Update CI env setup
.env.example (MODIFIED)                → Verify all vars in CI
config/**/*.php (MODIFIED)             → Validate config caching
                                         
.github/workflows/*.yml                → Syntax validation
phpunit.xml / pest.xml                 → Update test discovery
tailwind.config.ts                     → Add CSS build step
vite.config.ts                         → Add asset build step
tsconfig.json                          → Validate TypeScript config
eslint.config.js                       → Add linting step
pint.json                              → Add PHP formatting check
```

## Quick Action Prompts

Use these prompts with this skill to get targeted updates:

### 📦 Dependency Management
- "I just ran `composer require package-name`. Update CI to test with it."
- "Added a dev dependency `npm install --save-dev eslint`. Update workflows."
- "Updated PHP to 8.3. Check CI workflows and update all PHP versions."

### ✨ New Features
- "Created new models: User, Post. Update CI tests."
- "Added Fortify actions in app/Actions/Fortify/. Update auth test CI."
- "Created new API controllers in app/Http/Controllers/Api/. Update CI."

### 🗄️ Data Changes
- "Added 3 new migrations. Verify CI runs them fresh."
- "Created DatabaseSeeder. Add seed verification to CI."
- "Added a factory for the new User model. Update test setup."

### 🐳 Infrastructure
- "Updated docker-compose.yml to add Redis cache. Update CI workflows."
- "Added PostgreSQL service. Ensure CI sets up database correctly."
- "Added Minio for file storage. Configure CI for S3-compatible testing."

### 🧪 Test Suite
- "Added Pest plugin for Laravel. Update test runner."
- "Created feature tests for the auth system. Detect and run them."
- "Added model factory tests. Include in CI coverage."

### ⚙️ Configuration
- "Added new .env variables for the payment system. Update CI."
- "Changed authentication guard to API. Verify CI config."
- "Added Redis session driver. Update CI service setup."

### 🚀 Deployment
- "Ready to set up production deployment workflow."
- "Add staging environment CI/CD workflow."
- "Create cron-based backup validation workflow."

## Execution Checklist

When running this skill:

- [ ] **Scan Phase**: Identify all changed files since last workflow update
- [ ] **Classify Phase**: Map each file to its change category (dependency, feature, test, config, etc.)
- [ ] **Impact Phase**: Determine what CI steps need adding/updating/removing
- [ ] **Validation Phase**: Check workflow syntax and dependencies
- [ ] **Proposal Phase**: Present suggested changes with before/after
- [ ] **Application Phase**: Apply changes and test on a branch
- [ ] **Verification Phase**: Confirm first CI run passes with new changes

## Example Usage Flow

```
User: "I added a new Laravel feature with migrations and tests. Update CI."

Skill Response:
1. Scan: Detects app/Models/Post.php, database/migrations/2026_04_11_create_posts_table.php, tests/Feature/PostTest.php
2. Classify: Feature (model) + Database (migration) + Test (feature test)
3. Impact: Need to add migration step AND add feature test discovery
4. Validation: Check for database service in CI, verify PHP version supports migration syntax
5. Proposal: "Add these steps to your test workflow..."
6. Apply: "Ready to create/update .github/workflows/tests.yml?"
7. Verify: "Run the workflow on a branch to confirm it works"
```

## Related Skills & Customizations

After using this skill, you might want to set up:
- **Deployment workflows**: For staging/production deployments
- **Security scanning**: Dependabot, CodeQL, SAST tools
- **Performance testing**: Load testing, Lighthouse CI
- **Database workflows**: Backup verification, schema validation
- **Documentation generation**: API docs, changelog automation
