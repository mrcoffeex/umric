---
name: ci-cd-config-sync
description: "Detect codebase changes and suggest GitHub Actions CI/CD config updates. Use when: adding dependencies (composer/npm), creating new Laravel features, changing environment variables, modifying Docker Compose, or adding tests. Analyzes changes and proposes workflow updates to keep CI/CD in sync with your project."
---

# CI/CD Config Sync

Automatically detect project changes and synchronize GitHub Actions workflows with your codebase.

## When to Use This Skill

Trigger this skill when:
- ✅ Installing new PHP/Node dependencies (`composer require`, `npm install`)
- ✅ Adding new Laravel features (models, controllers, migrations, jobs)
- ✅ Changing environment variables or configuration
- ✅ Modifying Docker Compose services or configuration
- ✅ Adding new test files or changing test suite structure
- ✅ Adding scheduled tasks or background jobs
- ✅ Changing database schema or seed data

**Example prompts:**
- "I added Pest tests for my feature. Update CI/CD to run them on push."
- "We're adding Redis to Docker Compose. Update the workflows accordingly."
- "Added a Laravel job queue. Update CI config to handle async jobs."
- "Added new environment variables. Sync the CI setup."

## Skill Workflow

### Step 1: Analyze Change Files
First, identify which files changed and categorize them:

| File Pattern | Category | CI Impact |
|--------------|----------|-----------|
| `composer.json` / `package.json` | Dependencies | Install steps, versions |
| `app/Models/`, `app/Actions/`, `app/Http/Controllers/` | Features | Test discovery, coverage |
| `.env*`, `config/` | Configuration | Env setup, secrets |
| `docker-compose.yml` | Services | Service startup, health checks |
| `tests/` | Tests | Test commands, coverage |
| `database/migrations/`, `database/seeders/` | Database | Migration verification |
| `app/Jobs/` | Queue | Job testing, verification |
| `routes/` | Routes | Route validation |

### Step 2: Catalog Current Workflows
Check `.github/workflows/` for existing workflows:

```bash
ls -la .github/workflows/
```

Document each workflow's:
- **Name**: What it does (e.g., `tests.yml`, `deploy.yml`)
- **Trigger**: When it runs (push, pull_request, schedule)
- **Steps**: What it executes (install, test, build, deploy)
- **Env setup**: Which environment variables it uses

### Step 3: Suggest Updates by Change Type

#### **New Dependencies** (composer.json / package.json)
- **Check**: PHP version constraints, Node version constraints
- **Update**: Install step to include `composer install` / `npm install`
- **Add**: Lock file validation (composer.lock / package-lock.json)
- **Verify**: Check if new packages bring dev tools (PHPStan, ESLint, Prettier)

**Suggested workflow updates:**
```yaml
- name: Validate dependency changes
  run: |
    composer install --no-scripts
    npm install
    
- name: Check for security vulnerabilities
  run: |
    composer audit
    npm audit
```

#### **New Laravel Features** (Models, Controllers, Migrations, Actions)
- **Check**: If features include new migrations, jobs, or policies
- **Update**: Test discovery to include feature tests
- **Add**: Migration validation step if `database/migrations/` changed
- **Verify**: Check if new models need factories or seeders

**Suggested workflow updates:**
```yaml
- name: Run Laravel migrations
  run: php artisan migrate --force
  
- name: Validate models and policies
  run: php artisan model:show
  
- name: Run feature tests
  run: php artisan test tests/Feature --compact
```

#### **Environment Changes** (.env, config/)
- **Check**: Which environment variables are new
- **Update**: CI workflow .env setup or GitHub Secrets
- **Add**: Env validation before tests run
- **Verify**: Check for database credentials, API keys, secrets

**Suggested workflow updates:**
```yaml
- name: Validate environment configuration
  run: |
    php artisan config:cache
    php artisan config:show app
    
- name: Verify all required env vars
  run: php artisan tinker -e "echo config('app')"
```

#### **Docker Compose Changes**
- **Check**: New services (Redis, Postgres, Minio, etc.)
- **Update**: Service startup in CI workflow
- **Add**: Health check verification
- **Verify**: Port conflicts, service dependencies

**Suggested workflow updates:**
```yaml
- name: Start Docker services
  run: docker-compose up -d
  
- name: Wait for services
  run: |
    docker-compose exec -T postgres pg_isready || sleep 5
    docker-compose exec -T redis redis-cli PING || sleep 5
    
- name: Run database setup
  run: |
    docker-compose exec -T app php artisan migrate
```

#### **Test Suite Changes** (tests/)
- **Check**: New test files, new test patterns (Feature, Unit, Browser)
- **Update**: Test command to discover all tests
- **Add**: Coverage reporting if tests added
- **Verify**: Check for new test dependencies (Pest, Dusk, etc.)

**Suggested workflow updates:**
```yaml
- name: Run all tests
  run: php artisan test --compact --coverage
  
- name: Upload coverage reports
  uses: codecov/codecov-action@v3
  with:
    files: ./coverage/coverage.xml
```

### Step 4: Validation Checklist

Before applying changes, verify:

- [ ] All new dependencies are installable without errors
- [ ] New PHP features follow Laravel conventions
- [ ] Environment variables don't expose secrets in logs
- [ ] Docker services are accessible and healthy
- [ ] Tests pass locally AND in CI environment
- [ ] Migrations can run fresh and rollback
- [ ] Coverage hasn't decreased significantly
- [ ] CI/CD workflow file syntax is valid (check with `yq` or online validator)

### Step 5: Apply and Test

1. **Create/update** `.github/workflows/` files with suggested changes
2. **Validate** workflow syntax:
   ```bash
   yamllint .github/workflows/*.yml
   ```
3. **Commit** changes and push to test on CI
4. **Monitor** first workflow run for errors
5. **Iterate** if any configuration tweaks needed

## Common Patterns

### Pattern: Multi-PHP Version Testing
When adding PHP version constraints:
```yaml
strategy:
  matrix:
    php-version: ['8.2', '8.3']
runs-on: ubuntu-latest
steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php-version }}
```

### Pattern: Database Service Setup
When Docker Compose adds a database:
```yaml
services:
  postgres:
    image: postgres:15-alpine
    env:
      POSTGRES_PASSWORD: ${{ secrets.DB_PASSWORD }}
    options: >-
      --health-cmd pg_isready
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

### Pattern: Cache Management
When caching dependencies:
```yaml
- uses: actions/cache@v3
  with:
    path: |
      vendor
      node_modules
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
```

### Pattern: Artifact Uploads
When adding coverage or build artifacts:
```yaml
- uses: actions/upload-artifact@v3
  if: always()
  with:
    name: test-coverage
    path: coverage/
```

## Workflow Examples

### Complete Test Workflow
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15-alpine
        env:
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install
      - run: npm install
      - run: php artisan migrate --force
      - run: php artisan test --compact --coverage
```

### Linting & Code Quality Workflow
```yaml
name: Code Quality
on: [push, pull_request]
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: vendor/bin/pint --check
      - run: npm install
      - run: npm run lint
```

## Troubleshooting

**Q: Workflow fails with "Command not found"**
- A: Check if the tool is installed in the GitHub Actions runner. Add explicit install step.

**Q: Tests pass locally but fail in CI**
- A: Check for environment differences (PHP version, extensions, services running, database state).

**Q: Migrations fail in CI but pass locally**
- A: CI runs fresh database. Check for: missing migrations, hardcoded paths, local-only config.

**Q: Service can't connect in CI**
- A: Services are in separate containers. Use service name (e.g., `postgres` not `localhost`). Use `options: --health-cmd` for startup validation.

## Next Steps

After using this skill, consider:
- [ ] Adding **coverage reports** with Codecov integration
- [ ] Setting up **deployment workflows** for staging/production
- [ ] Adding **security scanning** (dependabot, code scanning)
- [ ] Creating **cron-based maintenance jobs** (database cleanup, log rotation)
- [ ] Setting up **notification workflows** (Slack alerts, email on failure)
