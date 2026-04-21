---
name: ulid-model-setup
description: 'Apply ULID primary keys to Laravel Eloquent models to hide integer IDs from URLs. Use when: adding a new model, migrating an existing model to ULID, preventing sequential ID exposure in routes, or asked to use ulid, non-sequential IDs, opaque IDs, or "hide ID from URL". Covers: HasUlids trait, migration schema changes (ulid() + foreignUlid()), factory updates, route model binding, Wayfinder compatibility, and fresh-migration vs add-column strategies.'
argument-hint: 'Model name or "all" to apply to all models'
---

# ULID Model Setup

Apply Laravel's built-in `HasUlids` trait so every model uses a 26-character sortable ULID as its primary key instead of an auto-increment integer. ULIDs are URL-safe, time-sortable, and never reveal record counts or insertion order.

## When to Use

- Creating a new model where the ID will appear in a URL
- Retrofitting an existing model that currently uses `$table->id()`
- Implementing best-practice opaque IDs across the codebase
- Any request involving "don't expose the ID", "hide the ID", or "use ULID/UUID"

## Core Concepts

| Concern          | Integer ID                  | ULID                            |
| ---------------- | --------------------------- | ------------------------------- |
| URL example      | `/papers/42`                | `/papers/01HV8...`              |
| Sequential?      | Yes — leaks count           | No — time-sorted but opaque     |
| Laravel support  | Built-in                    | `HasUlids` trait (Laravel 10+)  |
| Migration column | `$table->id()`              | `$table->ulid('id')->primary()` |
| FK column        | `$table->foreignId('x_id')` | `$table->foreignUlid('x_id')`   |

## Procedure

### Step 1 — Identify Scope

Determine which models to update. Check `app/Models/` and their corresponding migrations:

```bash
grep -rn "table->id()" database/migrations/
```

List every model whose `id` will appear in a URL (route model binding target).

### Step 2 — Add `HasUlids` to the Model

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ResearchPaper extends Model
{
    use HasFactory, HasUlids, SoftDeletes;   // add HasUlids
    // No other changes needed — Laravel handles key type/generation
}
```

> **No `$keyType` or `$incrementing` override needed.** `HasUlids` sets both automatically.

### Step 3 — Update the Migration

**Strategy A — Fresh migration (preferred for new tables or pre-production)**

Replace `$table->id()` with `$table->ulid('id')->primary()`:

```php
Schema::create('research_papers', function (Blueprint $table) {
    $table->ulid('id')->primary();          // ← replaces $table->id()
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUlid('category_id')->constrained()->cascadeOnDelete();
    // ... rest of columns unchanged
    $table->timestamps();
});
```

**Strategy B — Add-column migration (for live production databases)**

```php
// WARNING: Requires backfilling existing rows before switching PK
Schema::table('research_papers', function (Blueprint $table) {
    $table->ulid('ulid')->nullable()->after('id');
});
// Then backfill, swap PK, and drop old int column in separate steps.
// Only do this with a tested rollback plan.
```

Prefer Strategy A for this project (pre-production / seeded dev environment).

### Step 4 — Update Foreign Key Columns

Every `foreignId('model_id')` pointing at a ULID-keyed table must become `foreignUlid('model_id')`:

```php
// Before
$table->foreignId('research_paper_id')->constrained()->cascadeOnDelete();

// After
$table->foreignUlid('research_paper_id')->constrained()->cascadeOnDelete();
```

Search all migrations for the affected table's FKs:

```bash
grep -rn "foreignId('research_paper_id')" database/migrations/
```

### Step 5 — Update Factories

If `HasUlids` is applied, Laravel auto-generates the ULID in `newUniqueId()`. No factory change is needed for the primary key itself. However, factory FK states that hardcode integer IDs must use model instances instead:

```php
// Before (broken after ULID)
'research_paper_id' => 1,

// After
'research_paper_id' => ResearchPaper::factory(),
```

### Step 6 — Verify Route Model Binding

Laravel resolves route model binding by `{model}` → primary key by default. With `HasUlids`, the ULID is the primary key, so existing routes like:

```php
Route::get('/papers/{paper}', [ResearchPaperController::class, 'show']);
```

…work automatically. No `resolveRouteBinding()` override is required.

### Step 7 — Run and Verify

```bash
php artisan migrate:fresh --seed
php artisan test
```

Confirm:

- No `SQLSTATE[HY000]` type mismatch errors
- Routes resolve correctly with a ULID segment (e.g., `/papers/01HV8TVPNK...`)
- Factory states pass

## Checklist

- [ ] `HasUlids` added to every target model
- [ ] `$table->ulid('id')->primary()` in each model's create migration
- [ ] All `foreignId('x_id')` columns pointing at ULID tables changed to `foreignUlid('x_id')`
- [ ] Pivot tables with FK columns updated
- [ ] Factories that hardcode integer FK values updated
- [ ] `migrate:fresh --seed` runs without errors
- [ ] All existing feature tests pass

## Common Pitfalls

**Type mismatch on foreign keys** — If the parent uses ULID but the child still uses `foreignId` (bigint), MySQL/PostgreSQL will reject the constraint. Always update _both_ ends.

**Pivot tables used with `attach()/sync()`** — `BelongsToMany::attach()` and `sync()` bypass Eloquent model creation, so `HasUlids::newUniqueId()` is never called. Two choices:

- Pure pivot tables (no dedicated model) → revert their `id` to `$table->id()` (auto-increment); keep `foreignUlid` for the FK columns
- Pivot tables with a dedicated model → replace `attach()` calls with `ModelName::create([...])` so ULID is generated

**Alter migrations with `->change()`** — When an existing column is being made nullable via `->change()`, use the correct new column type. `foreignId('col')->nullable()->change()` will try to cast the column back to bigint. Use `foreignUlid('col')->nullable()->change()` or `char('col', 26)->nullable()->change()` instead.

**`nullableMorphs` in Spatie activity log** — The default `nullableMorphs('subject')` creates a bigint `subject_id`. Change to `nullableUlidMorphs('subject')` in the activity log migration to store ULID strings.

**`'integer'` casts on FK columns** — Any `'user_id' => 'integer'` entry in `$casts` will coerce the ULID string to an integer (e.g., `"01kpp..."` → `1`). Remove all `_id` integer casts from model `$casts` arrays.

**Sessions table FK** — `foreignId('user_id')` in the sessions table is not constrained. Replace with `$table->ulid('user_id')->nullable()->index()` (no `foreignUlid`, no `.constrained()`).

**Seeder hardcoded IDs** — Seeders that use `Model::find(1)` or raw integer IDs will break. Switch to `Model::first()` or factory-created instances.

**Activity log / Spatie** — `LogsActivity` stores the `subject_id` in the activity log. Requires `nullableUlidMorphs` in the activity log migration (see above).

**Wayfinder** — Route helper functions generated by Wayfinder accept the model instance or its key. With `HasUlids`, passing `$model->id` is already a ULID string — no Wayfinder changes required.

## This Project's Model List

Models in `app/Models/` that should receive `HasUlids` when their IDs appear in URLs:

| Model           | Table             | Notes                                                   |
| --------------- | ----------------- | ------------------------------------------------------- |
| `ResearchPaper` | `research_papers` | Primary URL-exposed model                               |
| `User`          | `users`           | Auth-handled; consider carefully (session/token impact) |
| `Category`      | `categories`      | Referenced in paper URLs                                |
| `Comment`       | `comments`        | Nested under papers                                     |
| `Announcement`  | `announcements`   | Admin-created, URL-exposed                              |
| `PanelDefense`  | `panel_defenses`  | Linked from paper detail                                |

> Apply to `User` only after reviewing Fortify session handling and existing auth tokens.
