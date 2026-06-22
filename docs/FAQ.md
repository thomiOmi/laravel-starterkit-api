# Frequently Asked Questions (FAQ)

## Setup & Configuration

### Q1: I already have `performance-improver.yml`, `security-checker.yml`, and `weekly-cleanup.yml`. Do I need to delete them?

**A:** Yes, delete the old ones before adding the new ones. They have conflicting names and the improved versions supersede them entirely.

```bash
# Delete old workflows
rm .github/workflows/performance-improver.yml
rm .github/workflows/security-checker.yml
rm .github/workflows/weekly-cleanup.yml

# Add new agents
mkdir -p .github/workflows/agents
# Copy new workflow files here
```

The new workflows are:
- Better Laravel-specific
- Safer (draft PRs, more guardrails)
- More intelligent (context-aware)
- Staggered (no conflicts)

---

### Q2: Where do I get the `JULES_API_KEY`?

**A:** It depends on your Jules provider:

**Option A: Using Google's Jules (google-labs-code/jules-action)**
- Sign up at [Google AI Studio](https://aistudio.google.com)
- Create API key
- Store as `JULES_API_KEY` in GitHub repo secrets

**Option B: Using Anthropic's Jules (if available)**
- Contact Anthropic sales
- Get API key from your account
- Store as `JULES_API_KEY`

**Option C: Self-hosted Jules**
- Run your own Jules instance
- Update workflow to point to your instance URL
- API key handling depends on your setup

```bash
# Test your API key works
curl -X POST https://api.anthropic.com/v1/messages \
  -H "Authorization: Bearer $JULES_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"model":"claude-sonnet-4-6","max_tokens":10,"messages":[{"role":"user","content":"Hi"}]}'
```

---

### Q3: Can I use these workflows with other Laravel projects besides my main codebase?

**A:** Yes, but you need separate repo instances. Each repository needs:
- Its own `.github/workflows/agents/` folder
- Its own `JULES_API_KEY` secret (can be same key, different repos)

You can't run one set of workflows across multiple repos unless:
1. You use a monorepo structure (single repo, multiple Laravel projects)
2. You use GitHub Actions matrix to run for each project separately

Example for monorepo:

```yaml
# .github/workflows/agents/agent-turbo-performance.yml
on:
  schedule:
    - cron: '0 4 * * *'

jobs:
  turbo-projects:
    strategy:
      matrix:
        project: [project-a, project-b, project-c]
    
    steps:
      - uses: actions/checkout@v4
      - name: Run TURBO for ${{ matrix.project }}
        working-directory: ${{ matrix.project }}
        run: |
          # Run TURBO against this project's directory
```

---

### Q4: I only want SENTINEL and CUSTODIAN, not TURBO. Can I disable TURBO?

**A:** Yes, three options:

**Option 1: Delete the workflow file**
```bash
rm .github/workflows/agents/agent-turbo-performance.yml
```

**Option 2: Disable via workflow (keep for later)**
```yaml
# In agent-turbo-performance.yml
jobs:
  turbo-scan:
    if: false  # Disabled
    runs-on: ubuntu-latest
```

**Option 3: Schedule it never**
```yaml
on:
  workflow_dispatch: # Manual only, never scheduled
  
jobs:
  turbo-scan:
    runs-on: ubuntu-latest
```

**Recommendation:** Start with all three, monitor for a month, then disable if not valuable.

---

## Prompts & Customization

### Q5: Can I modify the agent prompts?

**A:** Yes, absolutely. The prompts are in the workflow YAML files. Edit them directly.

```yaml
- name: Invoke Jules — TURBO Optimization Agent
  uses: google-labs-code/jules-action@v1.0.0
  with:
    prompt: |
      # EDIT THIS SECTION
      # Make it more/less aggressive, add domain-specific rules, etc.
```

**Common customizations:**

1. **Make agent more conservative:**
   ```
   Add to prompt: "Only if >95% confident. Skip if any doubt."
   ```

2. **Make agent more aggressive:**
   ```
   Add to prompt: "Be more liberal. Suggest opportunities even if small impact."
   ```

3. **Add domain-specific rules:**
   ```
   Add: "Our app uses Repository pattern. Prioritize Repository optimizations."
   ```

4. **Exclude certain patterns:**
   ```
   Add: "Never touch files in app/Jobs/ (async processing, context-dependent)"
   ```

---

### Q6: Can I create custom agents beyond TURBO/SENTINEL/CUSTODIAN?

**A:** Yes, but keep it simple. Create new workflow files following the same pattern:

```yaml
# .github/workflows/agents/agent-lighthouse-audit.yml
name: Agent LIGHTHOUSE — Web Performance Audit

on:
  schedule:
    - cron: '0 6 * * 0'  # Sunday 6 AM (after CUSTODIAN)
  workflow_dispatch:

jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Invoke Jules — LIGHTHOUSE Agent
        uses: google-labs-code/jules-action@v1.0.0
        with:
          prompt: |
            You are LIGHTHOUSE, a web performance auditor.
            
            Analyze frontend performance:
            - Missing lazy loading on images
            - Unoptimized CSS/JS bundles
            - Render-blocking resources
            - Excessive Third-party scripts
            
            [rest of custom prompt]
          jules_api_key: ${{ secrets.JULES_API_KEY }}
          draft: true
```

**Caution:** More agents = more PRs = more team burden. Start with three.

---

### Q7: The improved prompts are embedded in YAML. Can I move them to separate files for easier editing?

**A:** Yes, you can store prompts as files and reference them:

**Step 1: Create prompt files**
```
.github/
├── workflows/
│   └── agents/
│       └── agent-turbo-performance.yml
├── prompts/
│   ├── turbo.md
│   ├── sentinel.md
│   └── custodian.md
```

**Step 2: Use action to read prompt**
```yaml
- name: Read TURBO prompt
  id: prompt
  run: echo "TURBO_PROMPT=$(cat .github/prompts/turbo.md)" >> $GITHUB_OUTPUT

- name: Invoke Jules with prompt from file
  uses: google-labs-code/jules-action@v1.0.0
  with:
    prompt: ${{ steps.prompt.outputs.TURBO_PROMPT }}
    jules_api_key: ${{ secrets.JULES_API_KEY }}
```

**Benefit:** Version control prompts separately, easier team collaboration.

---

## PR Review & Merging

### Q8: An agent PR looks good but tests pass locally for me and fail in CI. Should I still merge?

**A:** No. Debug first.

**Steps:**
1. **Check the test failure** in PR Checks tab
2. **Run tests locally with exact same setup:**
   ```bash
   php --version  # Match CI PHP version
   composer update
   vendor/bin/pest --no-interaction
   ```
3. **If test fails locally too:**
   - Close the PR
   - Fix the underlying issue
   - Rerun agent after fix

4. **If test passes locally but fails in CI:**
   - Could be environment difference (database, cache, etc.)
   - Close the PR and investigate CI setup
   - This is a CI configuration issue, not an agent issue

---

### Q9: Can I auto-merge certain agent PRs?

**A:** Yes, but carefully. Only for very low-risk changes:

```yaml
- name: Auto-approve dead code removals (CUSTODIAN only)
  if: |
    steps.custodian.outputs.pr_created == 'true' &&
    contains(steps.custodian.outputs.pr_body, 'Removed unused import')
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.pulls.createReview({
        owner: context.repo.owner,
        repo: context.repo.repo,
        pull_number: context.issue.number,
        event: 'APPROVE'
      });

- name: Auto-merge if approved + tests pass
  if: success()
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.pulls.merge({
        owner: context.repo.owner,
        repo: context.repo.repo,
        pull_number: context.issue.number,
        merge_method: 'squash'
      });
```

**Safe to auto-merge:**
- ✅ Unused import removal
- ✅ Unused variable removal
- ✅ Unreachable code removal

**Never auto-merge:**
- ❌ Performance optimizations (need measurement)
- ❌ Security fixes (need review)
- ❌ Refactoring extractions (could introduce coupling)

---

### Q10: A developer disagrees with an agent PR. What do I do?

**A:** Close the PR and document the decision.

**Example:**
```
Agent suggested: Rename $data → $userIds

Developer comment: "We prefer $data for generic processing results 
across our codebase. Closing as design preference."

Then: Update team naming conventions document.
```

For future: You can adjust the prompt to match team preference.

---

## Performance & Measurement

### Q11: How do I measure if a TURBO PR actually improves performance?

**A:** Use these tools:

**Option 1: Laravel Debugbar (Development)**
```php
// Before optimization
Route::get('/leases', function () {
    $leases = Lease::all();  // 1 + N queries
    return response()->json($leases);
});

// After optimization
Route::get('/leases', function () {
    $leases = Lease::with('resident', 'room')->get();  // 3 queries total
    return response()->json($leases);
});

// In Debugbar: Compare query count
```

**Option 2: SPX Profiler (Recommended)**
```bash
# Install
composer require --dev siler/siler

# Run before optimization
php -d zend_extension=siler.so artisan tinker
>>> Lease::all();

# Compare execution time with after optimization
```

**Option 3: Benchmarking Script**
```php
// tests/Performance/LeasePerformanceTest.php
use PHPUnit\Framework\TestCase;

class LeasePerformanceTest extends TestCase {
    public function test_list_leases_under_100ms() {
        $start = microtime(true);
        $leases = Lease::with('resident')->get();
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, "Leases query took {$elapsed}ms");
    }
}
```

**Best practice:** Add the benchmark to your test suite, verify before/after.

---

### Q12: Agent says "reduces queries from 5 to 1" but I measured only 2 reduction. Why the discrepancy?

**A:** Agent made an estimate without full context. This is normal.

**Reasons for discrepancy:**
1. **Agent counted worst-case scenario** (agent thought 5 objects, actually 2)
2. **Eager loading already happened elsewhere** (in controller vs view)
3. **Query cache hid the cost** (subsequent queries were cached)
4. **Agent misunderstood relationship depth** (one-to-many vs many-to-many)

**What to do:**
1. **Verify actual measurement** (your measurement is correct)
2. **Update PR description** with real numbers
3. **Close if impact is too small** (if you were expecting 4-query reduction but got 1, maybe not worth it)
4. **Next time: Trust your measurement, not agent's estimate**

---

## Multi-Tenant & Authorization

### Q13: My app is multi-tenant with cove-scoped roles. Will agents understand this?

**A:** Partially. The prompts mention multi-tenant awareness, but they have limits.

**What agents will understand:**
- ✅ Missing `.where('tenant_id', ...)` calls
- ✅ Authorization gates that reference tenant
- ✅ Mass assignment on tenant_id field

**What agents won't understand:**
- ❌ Implicit tenant scoping via middleware
- ❌ Cove-scoped permission cascade logic
- ❌ When removing "unused" code actually breaks tenant isolation

**To improve agent understanding:**

Add to your prompt:
```
## Multi-Tenant Context

Your app uses cove-scoped permissions:
- Cove = unit of multi-tenancy (like "boarding house")
- All data queries must be scoped to current cove
- Authorization gates check both permission AND cove scope

Pattern:
✅ Good: User::whereCoveId($this->coveId)->get()
❌ Bad: User::all()  (exposes other coves' data)

When removing code:
- Never remove tenant_id WHERE clauses
- Never remove cove scope checks
```

---

### Q14: Agent flagged a permission check as "missing auth" but I have `->middleware('auth')`. Why?

**A:** Agent only sees the flagged line, not surrounding context.

**Solution: Add clarifying comment**
```php
// Security: Authorization enforced by middleware('auth')
// Further permission checks in middleware or gate
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');
```

Agent will see the comment next time and skip the false positive.

---

## Scaling & Best Practices

### Q15: We have 10 developers. Should we set up one shared agent account or per-developer?

**A:** One shared account per repository (not per developer).

**Why:**
- Single source of truth for agent changes
- Consistent PRs across team
- Simpler secret management (one `JULES_API_KEY`)
- Easier to track metrics

**Per-repository approach:**
```
repo: laravel-api
├── JULES_API_KEY: [shared key]
├── Agent PRs: reviewed by anyone

repo: nuxt-frontend
├── JULES_API_KEY: [same or different key]
├── Agent PRs: reviewed by anyone
```

---

### Q16: Our CI/CD pipeline is complex. Will agents interfere?

**A:** No, if configured properly.

**Best practices:**
1. **Agents run on schedule**, not on every commit
2. **Agents create draft PRs**, requiring manual review
3. **Agents don't auto-merge** (except dead code, optionally)
4. **Agent PRs go through same CI/CD as normal PRs**

**Potential conflict points:**

| Scenario | Risk | Mitigation |
|----------|------|-----------|
| Agent runs during deployment | High | Schedule agents outside deployment window |
| Agent PR merges during production incident | Medium | Add code freeze label to pause agents |
| Agent creates PR right before release | Low | Run agent one week before release cutoff |

**Safe setup:**
```yaml
# agent-turbo-performance.yml
on:
  schedule:
    - cron: '0 4 * * *'  # 4 AM, safe window

jobs:
  turbo:
    if: |
      github.event_name == 'workflow_dispatch' || 
      (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop')
    # Doesn't interfere with other workflows
    runs-on: ubuntu-latest
```

---

### Q17: How do I stop agents temporarily (e.g., during a release)?

**A:** Use code freeze label or conditional workflow:

**Option 1: Code Freeze Label**
```yaml
if: !contains(github.event.repository.topics, 'code-freeze')
```

Set label via:
```bash
gh repo edit --add-topic code-freeze
# To unfreeze:
gh repo edit --remove-topic code-freeze
```

**Option 2: Disable via workflow file**
```yaml
jobs:
  turbo:
    if: false  # Temporarily disabled
    runs-on: ubuntu-latest
```

**Option 3: Manual trigger only**
```yaml
on:
  workflow_dispatch:  # No schedule, manual only
```

---

## Troubleshooting Real Issues

### Q18: Agent keeps creating the same PR over and over. Why?

**A:** Agent doesn't track what it's already done. It rescans entire codebase each run.

**If same PR is suggested multiple times:**
1. **Merge it** (if it was good the first time)
2. **Close and lock it** (if not valuable, prevent re-creation)
3. **Update prompt** to skip that pattern

**Example: Agent keeps suggesting same eager load**

```
In prompt, add:
"If a relationship is already eager-loaded in the controller layer,
don't suggest it again in views."
```

---

### Q19: Tests pass locally but fail in agent's PR. What's different?

**A:** Environment differences between local and CI:

| Factor | Local | CI |
|--------|-------|-----|
| Database | SQLite (local) | MySQL/Postgres (CI) | 
| Cache | File/Redis | Fresh (CI) |
| PHP | Your version | CI PHP version |
| Composer | Cached deps | Fresh install |

**Debug:**
```bash
# Match CI environment
php --version           # Check PHP version
composer install        # Fresh install, match CI lockfile
vendor/bin/pest --no-interaction
```

---

### Q20: Agent made changes that work but introduce technical debt. Should I merge anyway?

**A:** No. Agent's job is to improve code, not create shortcuts.

**Example:**
```
Agent suggests: Remove relationship eager loading "because it's not used in this view"
But: Other code depends on it; lazy loading will cause N+1 elsewhere

Action: Close the PR. Explain to agent why this is not a real improvement.
```

---

## Cost & ROI

### Q21: How much will using Jules agents cost?

**A:** Depends on your usage:

**API call volume:**
- 3 agents × 1 call/day (average) = 3 calls/day
- 3 calls/day × 30 days = 90 calls/month
- 90 calls/month × 12 months = 1,080 calls/year

**Pricing depends on Jules provider:**
- **Google Jules:** Check [Google AI Pricing](https://ai.google.dev/pricing)
- **Anthropic:** Contact sales
- **Self-hosted:** Your infrastructure cost

**Typical estimate:** $0–50/month depending on provider and volume.

**ROI:**
- **SENTINEL:** Catches real vulnerabilities → Saves security incident cost (potentially $10k–100k+)
- **TURBO:** Optimizes queries → Saves server costs (potentially $100–1000/month)
- **CUSTODIAN:** Reduces technical debt → Saves refactoring time (potentially 10–20 hours/month)

**Break-even:** Usually within first month if any agent finds valuable improvements.

---

### Q22: Can I limit agent spending if API costs become an issue?

**A:** Yes:

**Option 1: Rate limit agent runs**
```yaml
on:
  schedule:
    - cron: '0 4 * * 0'  # Weekly instead of daily
```

**Option 2: Reduce agent complexity**
```
Simpler prompts = faster API calls = lower cost
```

**Option 3: Use cheaper model**
If Jules supports multiple models:
```yaml
prompt: |
  Use claude-haiku-4.5 (faster, cheaper)
  # Trade accuracy for cost
```

---

## Integration with Existing Tools

### Q23: I use Cursor/VS Code/Aider for local development. Can agents work together?

**A:** Yes, but use them for different purposes:

| Tool | Purpose |
|------|---------|
| **Local (Cursor/VS Code)** | Real-time coding, interactive |
| **GitHub Agent** | Batch optimization, async |

**Workflow:**
1. You code locally with Cursor/VS Code (interactive)
2. Agents scan on schedule (async, non-blocking)
3. Agent PRs come in for review
4. You merge valuable ones, ignore rest

**No conflicts** because:
- Agents work on merged code (not your draft branches)
- Agents create PRs (not direct commits)
- You maintain full control

---

### Q24: Can I use agent PRs with Claude Code or GitHub Copilot?

**A:** Yes, agent PRs are normal GitHub PRs.

**Workflow:**
```
1. Agent creates PR
2. You review in Claude Code (ask Claude about PR changes)
3. You merge or request changes
4. Claude/Copilot sees PR history and learns from agent's changes
```

---

## Laravel-Specific Questions

### Q25: How will agents handle our Repository/Action pattern?

**A:** Agents are aware of this pattern.

**From CUSTODIAN prompt:**
```
Prefer extracting to:
1. Action classes (business logic)
2. Query builders (complex queries)
3. Rules (validation)
4. Scopes (query filters)
```

**Example: Agent sees duplicated action logic**
```php
// Before
public function storeLeaseAction($data) {
  $lease = Lease::create($data);
  // ... 10 lines of setup
  return $lease;
}

public function updateLeaseAction($data) {
  $lease = Lease::findOrFail($data['id']);
  // ... same 10 lines
  return $lease;
}

// After: Agent suggests extracting to Action class
// CreateLeaseAction, UpdateLeaseAction
```

---

### Q26: Will agents work with Spatie Permission (roles/permissions)?

**A:** Partially. Agents understand permission patterns but not deeply.

**What agents will catch:**
- ✅ `$guarded` missing on User model (mass assignment)
- ✅ Missing `->middleware('auth')` on protected routes
- ✅ Hardcoded role names (`hasRole('admin')`)

**What agents miss:**
- ❌ Permission cascade logic (role A implies permission B, which requires cove scope)
- ❌ Gate definitions with complex conditionals
- ❌ Permission/ability method misuse in context

**Improvement:** Add to SENTINEL prompt details of your permission structure.

---

### Q27: Our models use Pest v4 for testing. Will agents understand?

**A:** Yes, agents know about Pest.

**From prompts:**
```
- **Run Pest suite locally.** All tests must pass.
- Test baseline: See test-baseline.log for current test health
```

Agents will:
- ✅ Run `vendor/bin/pest` before/after changes
- ✅ Respect test structure (tests/Unit/, tests/Feature/)
- ✅ Look for test coverage in changed files

---

### Q28: We use ULIDs for public IDs (not incrementing). Any agent considerations?

**A:** No special handling needed, but tell agents about it.

**Why:** Agents don't assume ID structure, so no issues.

**Optional:** Add to prompt if agents frequently suggest ID-related optimizations:
```
## ID Strategy

- Public IDs: ULID (uuid-like, not incremental)
- Database IDs: UUID or Auto-increment (hidden from API)
- Queries must use public IDs for lookups
```

---

### Q29: Bearer token auth (not Sanctum cookies). Any agent issues?

**A:** No issues. Agents don't care about auth method.

**Safe:** Your security setup with Bearer tokens is fine. Agents will still catch:
- ✅ Missing `->middleware('auth')` on protected routes
- ✅ Hardcoded tokens in code
- ✅ Missing validation on auth-required endpoints

---

## When to Reach Out for Help

### Q30: I've tried everything and agents still don't work. What do I do?

**Check these first:**

1. **Is `JULES_API_KEY` set?**
   ```bash
   gh secret list | grep JULES_API_KEY
   ```

2. **Is workflow YAML syntax valid?**
   ```bash
   yamllint .github/workflows/agents/*.yml
   ```

3. **Is branch protected?**
   - Agents only run on `main` / `develop`
   - Check workflow `if:` condition

4. **Did agent actually run?**
   - Check GitHub → Actions tab
   - Look for workflow run logs

5. **Is API key valid?**
   ```bash
   curl -X POST https://api.anthropic.com/v1/messages \
     -H "Authorization: Bearer $JULES_API_KEY" \
     -d '{"model":"claude-sonnet-4-6","max_tokens":10,"messages":[{"role":"user","content":"Hi"}]}'
   ```

If all checks pass but agents still fail:
- **Contact Jules provider support** (Google AI, Anthropic, etc.)
- **Check agent action repo** for known issues
- **Reach out to team** for second pair of eyes

