# Agent Troubleshooting & Advanced Customization

## Troubleshooting Guide

### Issue 1: Agent Workflow Fails to Run

**Symptoms:**
- Workflow shows in Actions but never executes
- Status: "Waiting" or "Error"

**Debug Steps:**

```bash
# Check if secrets are set
gh secret list | grep JULES_API_KEY
# Expected output: JULES_API_KEY

# If missing:
gh secret set JULES_API_KEY -b "your-api-key"
```

**Common Causes:**

| Cause | Fix |
|-------|-----|
| `JULES_API_KEY` not set | Add to repo secrets (Settings → Secrets) |
| Workflow file syntax error | Validate YAML: `yamllint .github/workflows/agents/*.yml` |
| Branch filter excludes current branch | Update `if:` condition to include your branch |
| Concurrent job already running | Check concurrency group; wait for previous to finish |

---

### Issue 2: Agent Creates No PR (Silent Success)

**Symptoms:**
- Workflow completes successfully
- But no PR is created
- No errors shown

**Why This Happens:**

This is actually **correct behavior**. Agent found no opportunities. Jules evaluated the codebase and determined:

- **TURBO:** No N+1 queries, no inefficient patterns found
- **SENTINEL:** No security vulnerabilities detected
- **CUSTODIAN:** No dead code, duplication, or naming issues

**Verify:**

```bash
# Check workflow logs
gh run list --workflow=agent-turbo-performance.yml --limit 1
gh run view <run-id> --log
```

Look for:
```
- PR Created: false
- Status: success
```

This is healthy.

---

### Issue 3: Agent Creates PR But Tests Fail

**Symptoms:**
- Draft PR is created
- But PR status shows test failure
- Changes suggested are causing test failures

**Debug Steps:**

1. **Read the test output** in the PR's Checks tab
   ```
   GitHub → PR → Checks → agent-turbo-performance → Details
   ```

2. **Identify what broke**
   ```
   Pest output shows:
   ✓ tests/Unit/UserTest.php:42
   ✗ tests/Feature/LeaseControllerTest.php:15 — Unexpected query count
   ```

3. **Understand the change**
   - Open the Files Changed tab
   - See what Jules modified

**Common Causes & Fixes:**

| Issue | Cause | Fix |
|-------|-------|-----|
| Test count increased | Agent added eager loading that isn't cached | Review & close if not beneficial |
| Relationship returns null | Eager load missing; changed lazy-load timing | Add explicit null checks or close PR |
| Authorization test fails | Agent removed permission check thinking it's redundant | Review permission scope; close if scoped check exists |
| Assertion error in name comparison | Renamed variable but test asserts old name | Close; agent made wrong assumption |

**When to Merge Anyway:**

Only if:
1. Test failure is pre-existing (not caused by agent)
2. Test is flaky (intermittent failure)
3. You understand the failure and it's safe to fix separately

---

### Issue 4: Agent Suggests Wrong Optimization

**Symptoms:**
- Agent suggests eager loading but relationship already eager-loaded elsewhere
- Agent suggests caching but data changes frequently
- Agent suggests extraction that introduces coupling

**Cause:**
Jules doesn't have full request lifecycle context. It only sees git diff.

**Examples:**

```php
// Controller
public function index() {
  $users = User::with('posts')->get();  // ← Already eager-loaded
  return view('users.index', compact('users'));
}

// View
@foreach ($users as $user)
  @foreach ($user->posts as $post)  // ← Agent might suggest eager load here
    {{ $post->title }}
  @endforeach
@endforeach
```

**Fix:**
1. Review the PR
2. Understand the context (is it already loaded?)
3. Close if not beneficial
4. Update the prompt if pattern repeats:
   ```
   Add to TURBO prompt:
   "Don't suggest eager loading if model is already eager-loaded in controller"
   ```

---

### Issue 5: SENTINEL Flags False Positive

**Symptoms:**
- Agent marks permission check as "missing auth"
- Agent marks Blade variable as "XSS vulnerable"
- Agent marks field as "not encrypted" when it's encrypted elsewhere

**Cause:**
Jules only sees the flagged line, not surrounding context.

**Example:**

```php
// ❌ Agent flags as "missing auth"
Route::get('/dashboard', function () {
  return view('dashboard');
})->middleware('auth');  // ← Context: auth is enforced by middleware

// Agent might miss the middleware() call if it's not in the direct line
```

**Fix:**
1. **Review the false positive**
2. **Add a comment explaining** (for next agent run):
   ```php
   // Security: authorization enforced by middleware('auth')
   Route::get('/dashboard', function () {
     return view('dashboard');
   })->middleware('auth');
   ```
3. **Close the agent PR** if it suggests unnecessary changes
4. **Adjust the prompt** if pattern repeats:
   ```
   Add to SENTINEL prompt:
   "Skip endpoints with ->middleware('auth')"
   ```

---

### Issue 6: Agent Removes Code That Breaks Implicitly

**Symptoms:**
- CUSTODIAN removes a method
- Tests pass locally
- But production breaks (Observer calls removed method)
- Or a macro/helper is removed that's used via magic __call

**Cause:**
Julia doesn't understand Laravel's dynamic calling (Observers, Macros, Facades with __call).

**Example:**

```php
// User model
class User extends Model {
  public function notifyOfPasswordReset() {  // ← Agent removes as "unused"
    // Sends email
  }
}

// UserObserver (not visible to agent)
public function updated(User $user) {
  if ($user->isDirty('password')) {
    $user->notifyOfPasswordReset();  // ← Breaks when method removed
  }
}
```

**Prevention:**

Make sure the **CUSTODIAN prompt's guardrails include:**
```
### ALWAYS SKIP
- Observer methods (even if not directly referenced)
- Trait methods that might be called polymorphically
- Methods registered via ServiceProvider
```

The improved prompts already have this, but verify your version does.

**Fix:**
1. **Revert the commit** or close the PR
2. **Add a comment** explaining the Observer dependency:
   ```php
   // Called by UserObserver when password changes
   public function notifyOfPasswordReset() { }
   ```

---

## Advanced Customization

### Option 1: Per-Agent Scheduling

Run agents at different frequencies:

**Every Hour (Aggressive):**
```yaml
on:
  schedule:
    - cron: '0 * * * *'  # Every hour
```

**Twice Daily:**
```yaml
on:
  schedule:
    - cron: '0 4,16 * * *'  # 4 AM and 4 PM UTC
```

**Only on Weekdays:**
```yaml
on:
  schedule:
    - cron: '0 4 * * 1-5'  # Mon-Fri 4 AM UTC (1=Mon, 5=Fri)
```

---

### Option 2: Branch-Specific Agents

Run different agents on different branches:

```yaml
# agent-turbo-staging.yml (only on develop)
on:
  schedule:
    - cron: '0 4 * * *'

jobs:
  turbo:
    if: github.ref == 'refs/heads/develop'  # Only develop
    runs-on: ubuntu-latest
    # ...
```

```yaml
# agent-turbo-production.yml (only on main)
on:
  schedule:
    - cron: '0 4 * * *'

jobs:
  turbo:
    if: github.ref == 'refs/heads/main'  # Only main
    runs-on: ubuntu-latest
    # ...
```

---

### Option 3: Conditional Agent Execution

Skip agents based on commit message:

```yaml
jobs:
  turbo-scan:
    # Skip if commit message contains [skip-turbo]
    if: |
      !contains(github.event.head_commit.message, '[skip-turbo]') &&
      (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop')
    runs-on: ubuntu-latest
    # ...
```

**Usage:**
```bash
git commit -m "refactor: massive refactor [skip-turbo]"
# Workflow will skip TURBO agent but still run others
```

---

### Option 4: Agent Output to Slack/Email

Notify team of agent PRs:

```yaml
- name: Notify Slack on PR Created
  if: steps.turbo.outputs.pr_created == 'true'
  uses: slackapi/slack-github-action@v1
  with:
    webhook-url: ${{ secrets.SLACK_WEBHOOK }}
    payload: |
      {
        "text": "🚀 Performance optimization suggested",
        "blocks": [
          {
            "type": "section",
            "text": {
              "type": "mrkdwn",
              "text": "TURBO Agent created a PR\n${{ steps.turbo.outputs.pr_url }}"
            }
          }
        ]
      }
```

---

### Option 5: Auto-Approve Specific PRs

Automatically approve low-risk agent PRs:

```yaml
- name: Auto-approve CUSTODIAN dead code removals
  if: |
    steps.custodian.outputs.pr_created == 'true' &&
    contains(steps.custodian.outputs.pr_title, 'dead code')
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.pulls.createReview({
        owner: context.repo.owner,
        repo: context.repo.repo,
        pull_number: context.issue.number,
        event: 'APPROVE',
        body: 'Auto-approved: low-risk dead code removal'
      });
```

**Caution:** Only auto-approve if you're confident in agent accuracy.

---

### Option 6: Custom Prompts Per Module

Different prompts for different parts of codebase:

```yaml
# agent-turbo-api.yml
jobs:
  turbo-api:
    if: |
      contains(github.event.pull_request.files, 'app/Http/Controllers/Api/*')
    runs-on: ubuntu-latest
    steps:
      # ... checkout, install deps ...
      - name: Invoke Jules for API Performance
        uses: google-labs-code/jules-action@v1.0.0
        with:
          prompt: |
            # TURBO API-Specific Prompt
            
            Focus on API performance patterns:
            - N+1 in API endpoints (higher impact than views)
            - Response serialization inefficiencies
            - Pagination without filters
            - API rate limiting anti-patterns
            
            Ignore:
            - Frontend eager loading (not applicable)
            - View rendering optimizations
            
            [rest of prompt]
```

---

### Option 7: Integration with Pull Request Labels

Label agent PRs for filtering:

```yaml
- name: Label agent PR
  if: steps.turbo.outputs.pr_created == 'true'
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.issues.addLabels({
        owner: context.repo.owner,
        repo: context.repo.repo,
        issue_number: context.issue.number,
        labels: ['agent-turbo', 'performance', 'draft']
      });
```

Benefits:
- Easily filter agent PRs in GitHub UI
- Automate stale PR detection ("label:agent-turbo is:open created:<30days-ago")
- Dashboard metrics ("how many agent PRs merged vs closed")

---

### Option 8: Agent Dry-Run Mode

Test agent suggestions without creating PR:

```yaml
- name: Jules Dry-Run (comment only)
  uses: google-labs-code/jules-action@v1.0.0
  with:
    prompt: |
      [agent prompt]
    draft: false
    auto_commit: false  # Don't commit changes
    # Then in a script:
    
- name: Comment with Dry-Run Suggestions
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.issues.createComment({
        issue_number: context.issue.number,
        body: `## 🤔 TURBO Dry-Run Suggestions (No Changes)\n${dryRunOutput}`
      });
```

Use case: Test agent behavior on specific PR without creating noise.

---

### Option 9: Conditional Agent Rules

Different guardrails based on branch/context:

```yaml
jobs:
  turbo:
    env:
      # Stricter on main, looser on develop
      MIN_IMPACT_QUERIES: ${{ github.ref == 'refs/heads/main' && '5' || '2' }}
      MAX_CHANGE_LINES: ${{ github.ref == 'refs/heads/main' && '30' || '50' }}
    
    steps:
      - name: Invoke Jules with conditional rules
        uses: google-labs-code/jules-action@v1.0.0
        with:
          prompt: |
            # TURBO
            
            Performance thresholds:
            - Only optimize if saving ≥${{ env.MIN_IMPACT_QUERIES }} queries
            - Changes must be ≤${{ env.MAX_CHANGE_LINES }} lines
            
            [rest of prompt]
```

---

### Option 10: Custom Test Runner Integration

Run domain-specific tests before agent creates PR:

```yaml
- name: Run performance-specific tests
  run: |
    vendor/bin/pest --filter="Performance" --no-interaction

- name: Run security-specific tests
  run: |
    vendor/bin/pest --filter="Security" --no-interaction

- name: Run quality-specific tests
  run: |
    vendor/bin/pest --filter="Quality" --no-interaction

- name: Invoke Jules with test context
  uses: google-labs-code/jules-action@v1.0.0
  with:
    prompt: |
      [agent prompt]
      
      Test baseline:
      ${{ steps.performance-tests.outputs.summary }}
```

---

## Prompt Customization Examples

### Custom TURBO for Multi-Tenant Queries

```yaml
prompt: |
  # TURBO — Multi-Tenant Optimizations

  Special focus for multi-tenant (cove-scoped) models:
  
  ### Multi-Tenant Specific Patterns
  - Missing whereHasScope('tenant_id') before relationships
  - Eager loading across tenant boundaries (security + perf issue)
  - Repeated tenant_id filtering instead of scope
  
  Example (Bad):
  ```php
  $leases = Lease::with('resident')->get();
  // Loads residents from ALL tenants, not current tenant
  ```
  
  Example (Good):
  ```php
  $leases = Lease::whereTenantId($this->tenant_id)
      ->with('resident')
      ->get();
  ```
  
  [rest of TURBO prompt with multi-tenant focus]
```

---

### Custom SENTINEL for Permission-Heavy App

```yaml
prompt: |
  # SENTINEL — Permission-Scoped Security

  Your app uses Spatie Permission with cove-scoped roles.
  
  ### Special Cases
  
  ✅ Safe (permission checks are cove-scoped):
  ```php
  if ($user->can('edit-lease')) {
    // Safe: can() checks tenant scope via gate
  }
  ```
  
  ❌ Unsafe (missing tenant scope):
  ```php
  if ($user->hasRole('admin')) {
    // Unsafe: hasRole() is global, not scoped to tenant
  }
  ```
  
  ### Rules
  - Flag `hasRole()` without explicit `->scoped()` check
  - Flag `can()` if permission definition is missing tenant scope
  - Skip if `gate()->define()` includes cove scope logic
  
  [rest of SENTINEL prompt]
```

---

### Custom CUSTODIAN for Modular Architecture

```yaml
prompt: |
  # CUSTODIAN — Modular Architecture Cleanup

  Your codebase uses modular patterns:
  - Repository classes for data access
  - Action classes for business logic
  - Domain-scoped concerns
  
  ### Extraction Opportunities
  
  Prefer extracting to:
  1. **Action classes** (business logic)
  2. **Query builders** (complex queries)
  3. **Rules** (validation)
  4. **Scopes** (query filters)
  
  Don't extract to:
  - Traits (prefer composition via dependency injection)
  - Utils/Helpers (create Action instead)
  - Services (already have Actions)
  
  [rest of CUSTODIAN prompt]
```

---

## Monitoring & Metrics

### Track Agent Effectiveness

```bash
#!/bin/bash
# Count merged agent PRs per agent

echo "TURBO PRs (merged):"
gh pr list --state=merged --search="Turbo:" --json=number | wc -l

echo "SENTINEL PRs (merged):"
gh pr list --state=merged --search="Sentinel:" --json=number | wc -l

echo "CUSTODIAN PRs (merged):"
gh pr list --state=merged --search="Custodian:" --json=number | wc -l

echo "Total agent PRs (open/draft):"
gh pr list --search="label:agent-turbo OR label:agent-sentinel OR label:agent-custodian" --json=number | wc -l
```

### Dashboard Ideas

- Weekly agent PR creation rate
- Merge rate (merged / created)
- Time-to-merge (avg days in draft)
- Code churn from agents (lines added/removed)

