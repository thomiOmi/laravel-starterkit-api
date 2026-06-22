# Agent Comparison Matrix & Quick Reference

## At a Glance

| Aspek | TURBO | SENTINEL | CUSTODIAN |
|-------|-------|----------|-----------|
| **Purpose** | Performance optimization | Security hardening | Code quality/maintenance |
| **Schedule** | Daily 4 AM UTC | Daily 5 AM UTC | Weekly Monday 2 AM UTC |
| **Ideal PR Rate** | ~1–2 per week | ~1–3 per month | ~1 per month |
| **Risk Level** | Medium | Low (CRITICAL) → High (MEDIUM) | Low |
| **Review Effort** | Medium (measure impact) | High (verify fix) | Low (obvious cleanups) |
| **False Positive Rate** | ~20–30% | ~5–10% (CRITICAL only) | ~10–15% |
| **Auto-merge Safe?** | ❌ No | ⚠️ Only CRITICAL | ✅ Yes (dead code only) |
| **Needs Domain Knowledge?** | ✅ Yes (schema, relationships) | ✅ Yes (permission scope) | ❌ No |
| **Test Coverage Required?** | ✅ Yes (in changed file) | ✅ Yes (HIGH/MEDIUM) | ✅ Yes (removals) |

---

## Agent Lifecycle: What Happens When Jules Runs

```
┌─────────────────────────────────────────────────────────────┐
│ Scheduled Time (or Manual Trigger via workflow_dispatch)    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 1. Checkout repo + Install dependencies                     │
│    - PHP version verified                                   │
│    - Composer dependencies installed                        │
│    - Cache hit if unchanged                                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Run baseline tests (pre-agent)                           │
│    - Pest suite runs completely                            │
│    - Output logged to test-baseline.log                    │
│    - Continue on error (pass to Jules for context)         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Invoke Jules with improved prompt + context              │
│    - Framework version, PHP version passed as env vars     │
│    - Test baseline context included in prompt              │
│    - Git diff analyzed by Jules                            │
│    - Pattern matching (N+1, mass assignment, etc)          │
└─────────────────────────────────────────────────────────────┘
                            ↓
                    ┌───────┴───────┐
                    ↓               ↓
        ┌─────────────────┐  ┌──────────────────┐
        │ Opportunity     │  │ No opportunity   │
        │ Found?          │  │ Found            │
        └────────┬────────┘  └──────────┬───────┘
                 ↓                      ↓
        ┌─────────────────┐  ┌──────────────────┐
        │ Generate PR     │  │ Exit gracefully  │
        │ (draft mode)    │  │ (0 changes)      │
        └────────┬────────┘  └──────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Post-optimization test verification                      │
│    - Pest suite runs on modified code                      │
│    - If tests fail → PR still created (for review)         │
│    - If tests pass → PR created with confidence            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Add guardrails comment to PR                             │
│    - Checklist of what to verify                           │
│    - Framework-specific context                            │
│    - Non-automated concerns flagged                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Workflow summary logged                                  │
│    - PR created: yes/no                                    │
│    - PR URL (if created)                                   │
│    - Job status                                            │
└─────────────────────────────────────────────────────────────┘
```

---

## Example PR Outputs

### TURBO Example: N+1 Query Optimization

```markdown
🚀 Turbo: eager load relationships in LeaseController

💡 Pattern: N+1 query on relationships
🎯 Why: 1 + N queries for each lease (residence, tenant, room, addons)
📊 Impact: Reduced from 5 queries per lease to 1 query
🔬 Method: Ran Pest suite locally; all 42 tests pass

---

## Changes

### app/Http/Controllers/LeaseController.php

```php
- $leases = Lease::where('status', 'active')->get();
+ $leases = Lease::where('status', 'active')
+     ->with(['residence', 'tenant', 'room', 'addons'])
+     ->get();
```

### Why This Works

- **Before:** 1 query for leases + 4 queries per lease = 1 + (N×4) queries
- **After:** 1 query for leases + 1 query per relationship = 5 queries total
- **Measurable:** 20 leases = 81 queries → 6 queries (98% reduction)

### Test Coverage

✅ `LeaseControllerTest::test_index_returns_active_leases`
✅ `LeaseControllerTest::test_index_respects_pagination`
✅ `LeaseRepositoryTest::test_get_active_leases`
```

---

### SENTINEL Example: Mass Assignment Vulnerability

```markdown
🔒 Sentinel: add mass assignment protection to User model

🚨 Severity: HIGH
⚠️  What: User model missing $guarded; could expose sensitive fields
🛡️  Fix: Added $guarded = ['id', 'password', 'two_factor_secret']

---

## Changes

### app/Models/User.php

```php
class User extends Model {
+   protected $guarded = ['id', 'password', 'two_factor_secret'];
    
    // ...
}
```

### Why This Matters

- **Before:** `User::create(request()->all())` could set `password` or admin flags
- **After:** Only whitelisted fields can be mass-assigned; others are protected
- **Test impact:** 0 (all existing tests still pass)

### Security Validation

✅ Mass assignment test enforces guarded fields
✅ FormRequest validation still catches invalid input
✅ No breaking changes to existing API
```

---

### CUSTODIAN Example: Dead Code Removal

```markdown
♻️  Custodian: remove unused imports and dead code

🧹 Changes:
- Removed unused `use DateTime` from LeaseService
- Removed unreachable code block in RoomRateCalculator (after return)
- Renamed `$data` → `$calculatedRates` in RoomService for clarity
- Extracted duplicate validation logic to RoomRateRequest rule

📊 Impact: Removes 8 unused lines; improves readability
🔬 Verified: Pest tests pass; PHPStan clean

---

## Changes

### app/Services/LeaseService.php

```php
- use DateTime;  // ← Removed (never used)
  use Carbon\Carbon;

  class LeaseService {
    public function calculateEndDate(Lease $lease) {
      return $lease->start_date->addDays($lease->duration_days);
      
-     // Dead code below (unreachable after return)
-     $calculation = [];
    }
  }
```

### app/Services/RoomService.php

```php
- $data = [];
+ $calculatedRates = [];
  foreach ($rooms as $room) {
-   $data[] = [
+   $calculatedRates[] = [
      'room_id' => $room->id,
      'daily_rate' => $room->daily_rate,
    ];
  }
- return $data;
+ return $calculatedRates;
```

### app/Http/Requests/RoomRateRequest.php

```php
+ // Extracted from duplicate validation in RoomController & RoomService
  public function rules(): array {
    return [
      'daily_rate' => 'numeric|min:0|max:999999',
      'monthly_rate' => 'numeric|min:0|max:999999',
    ];
  }
```
```

---

## Decision Tree: Should I Merge This PR?

### For TURBO (Performance)

```
┌─ Is there a clear performance metric?
│  ├─ NO  → ❌ Close (request measurement)
│  └─ YES → Continue
│
├─ Was the metric measured (before/after)?
│  ├─ NO  → ⚠️  Merge cautiously (trust Jules)
│  └─ YES → Continue
│
├─ Do the changes touch Observers/Events?
│  ├─ YES → ⚠️  Request code review (might break)
│  └─ NO  → Continue
│
├─ Do all Pest tests pass?
│  ├─ NO  → ❌ Close (fix the test failures)
│  └─ YES → Continue
│
└─ Is the change ≤50 lines?
   ├─ NO  → ⚠️  Request review (might hide complexity)
   └─ YES → ✅ MERGE
```

### For SENTINEL (Security)

```
┌─ Is severity CRITICAL?
│  ├─ YES → Continue (these are safe)
│  └─ NO  → Continue (but review harder)
│
├─ Does the fix change authorization logic?
│  ├─ YES → ⚠️  Request domain expert review (permission scope)
│  └─ NO  → Continue
│
├─ Do all Pest tests still pass?
│  ├─ NO  → ❌ Close (something broke)
│  └─ YES → Continue
│
├─ Is the fix obviously correct?
│  ├─ Hardcoded secret removal: ✅ YES
│  ├─ Missing validation: ✅ YES
│  ├─ Mass assignment fix: ✅ YES
│  ├─ Encryption on sensitive field: ✅ YES
│  └─ Authorization gate refactor: ❌ NO (needs review)
│
└─ Is the change ≤30 lines?
   ├─ NO  → ⚠️  Might hide logic (review carefully)
   └─ YES → ✅ MERGE
```

### For CUSTODIAN (Maintenance)

```
┌─ Is this a dead code removal?
│  ├─ Unused import: ✅ Safe
│  ├─ Unused variable: ✅ Safe
│  ├─ Unreachable code: ✅ Safe
│  ├─ Unused function: ⚠️  Check if called polymorphically
│  └─ Unused class: ⚠️  Check if registered in ServiceProvider
│
├─ Is this a naming improvement?
│  ├─ `$data` → `$userIds`: ✅ Obvious improvement
│  ├─ `process()` → `generateInvoice()`: ✅ Obvious improvement
│  └─ Anything else: ⚠️  Subjective (team preference?)
│
├─ Is this a duplication extraction?
│  ├─ Identical validation rules: ✅ Safe
│  ├─ Identical Eloquent chains: ✅ Safe
│  ├─ Identical error handling: ✅ Safe
│  └─ Duplicated business logic: ⚠️  Check for subtle differences
│
├─ Does it touch multi-tenant/permission logic?
│  ├─ YES → ❌ Close (too risky)
│  └─ NO  → Continue
│
├─ Do all Pest tests pass?
│  ├─ NO  → ❌ Close (something broke)
│  └─ YES → Continue
│
└─ Is the change ≤100 lines?
   ├─ NO  → ⚠️  Break into smaller PRs
   └─ YES → ✅ MERGE
```

---

## When to Close/Skip a PR

| Scenario | Agent | Action |
|----------|-------|--------|
| Agent suggests eager load that doesn't help | TURBO | Close + comment "no measurable impact" |
| Agent suggests refactoring with no tests | TURBO | Close + comment "no test coverage" |
| Agent flags "missing auth" on gated endpoint | SENTINEL | Close + explain your gate logic |
| Agent suggests fixing "XSS" in Blade escapes | SENTINEL | Close + comment "Blade auto-escapes" |
| Agent removes method used by Observer | CUSTODIAN | Close + comment "called implicitly" |
| Agent renames something you disagree with | CUSTODIAN | Close + comment "team naming convention" |
| Agent creates 10 PRs from one run | Any | Close most; keep 1–2 highest value |

---

## Resource Usage & Costs

### Per-Workflow Impact

| Agent | Compute Time | Disk Space | API Calls |
|-------|--------------|-----------|-----------|
| TURBO | ~2–3 min | 50 MB (deps) | 1 Jules call |
| SENTINEL | ~2–3 min | 50 MB (deps) | 1 Jules call |
| CUSTODIAN | ~2–3 min | 50 MB (deps) | 1 Jules call |
| **Total (all 3)** | **~9 min/week** | **N/A (cached)** | **~3 calls/week** |

### GitHub Actions Minutes

- Free tier: 2,000 minutes/month
- 3 agents × 3 min/run × ~30 days ÷ (daily + daily + weekly) = ~16 minutes/month
- **Cost impact:** < 1% of free tier

### Jules API Cost

- Check Jules pricing for API call rates
- Conservative estimate: 3 calls/week × 4 weeks = 12 calls/month
- Contact Jules support for volume pricing

---

## Recommended PR Review Checklist

### Every Agent PR Should Have:

- [ ] **Measurement or validation** (what metric proves this works?)
- [ ] **Test confirmation** (Pest tests pass?)
- [ ] **No side effects** (Observers, Listeners, Events unaffected?)
- [ ] **Changes ≤100 lines** (if larger, should be split)
- [ ] **Clear intent** (PR title + description explain the why)
- [ ] **No multi-tenant scope breaks** (if applicable)

### Agent-Specific Checklist

**TURBO:**
- [ ] Performance metric is measurable (queries saved, ms reduced)
- [ ] Doesn't add complexity for marginal gains
- [ ] Relationship loading won't cause memory issues on large datasets

**SENTINEL:**
- [ ] Fix is the recommended approach (not just one of many)
- [ ] Doesn't break existing security assumptions
- [ ] No hardcoded values where config should be used

**CUSTODIAN:**
- [ ] Removal makes sense in domain context
- [ ] Renamed items don't clash with existing naming
- [ ] Extracted code doesn't introduce coupling

