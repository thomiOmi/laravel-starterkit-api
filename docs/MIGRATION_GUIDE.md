# GitHub Actions Agent Workflows — Setup Guide

## File Structure

Replace your old workflows with:

```
.github/
├── workflows/
│   ├── agents/
│   │   ├── agent-turbo-performance.yml      # Daily 4 AM UTC
│   │   ├── agent-sentinel-security.yml      # Daily 5 AM UTC
│   │   └── agent-custodian-maintenance.yml  # Weekly Monday 2 AM UTC
│   ├── tests.yml                            # (existing)
│   └── deploy.yml                           # (existing)
```

---

## Key Differences from Original Workflows

### 1. **Schedule Staggering**

| Original | New | Reason |
|----------|-----|--------|
| Performance: 4 AM UTC | 4 AM UTC | Same |
| Security: 4 AM UTC | **5 AM UTC** | Avoid conflicts; Turbo finishes, then Security scans |
| Maintenance: 2 AM Monday | 2 AM Monday | Safe before business hours |

**Impact:** Fewer concurrent jobs, cleaner logs, easier debugging.

---

### 2. **Framework Context**

**Before:**
```yaml
prompt: |
  You are TURBO...
  # Generic advice for any PHP project
```

**After:**
```yaml
env:
  PHP_VERSION: '8.4'
  LARAVEL_VERSION: '13'
  TEST_FRAMEWORK: 'Pest v4'
  ARCHITECTURE: 'Modular (Repository/Action patterns)'

prompt: |
  # ... prompt includes ${{ env.LARAVEL_VERSION }} etc
```

**Impact:** Jules now knows your stack. Suggestions are Laravel-specific, not generic PHP.

---

### 3. **Test Baseline Context**

**Before:** No baseline.

**After:**
```yaml
- name: Run Pest baseline (pre-optimization)
  id: baseline_tests
  run: vendor/bin/pest --no-interaction 2>&1 | tee test-baseline.log
  continue-on-error: true

# ... then pass to Jules:
  # **Test baseline:** See test-baseline.log for current test health
```

**Impact:** Jules can see which tests are failing *before* it makes changes. Safer decisions.

---

### 4. **Safer Draft Mode**

**Before:**
```yaml
# No draft mode specified
# Jules creates ready-to-merge PRs
```

**After:**
```yaml
draft: true                           # Draft PR
auto_commit_message: 'turbo: ...'    # Clear origin
```

**Impact:** All agent PRs are drafts. Require manual review before merge. No surprise auto-merges.

---

### 5. **Automated PR Comments with Guardrails**

**Before:** No context comments.

**After:**
```yaml
- name: Comment on PR with measurement context
  if: steps.turbo.outputs.pr_url
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.issues.createComment({
        issue_number: context.issue.number,
        body: `## 🔬 Performance Agent Context

      **Guardrails:**
      - ✅ Must have Pest test coverage
      - ✅ No breaking of Observers/Events
      ...`
      });
```

**Impact:** Every agent PR gets a checklist comment. Reviewers know what to look for.

---

### 6. **Post-Action Test Verification**

**Before:** Tests run, but no connection to agent output.

**After:**
```yaml
- name: Verify Pest suite passes post-optimization
  if: steps.turbo.outputs.pr_created == 'true'
  run: vendor/bin/pest --no-interaction
  continue-on-error: true
```

**Impact:** If agent makes changes, tests re-run *in the workflow*. Early failure detection.

---

### 7. **Branch Filtering**

**Before:**
```yaml
# Runs on any branch (could create PRs from feature branches)
```

**After:**
```yaml
if: |
  github.event_name == 'workflow_dispatch' || 
  (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop')
```

**Impact:** Agents only scan main/develop, not feature branches. Cleaner PR history.

---

### 8. **Input Controls**

**Before:** No manual control.

**After:**

**TURBO:**
```yaml
workflow_dispatch:
  inputs:
    target_branch:
      description: 'Target branch for scan'
      default: 'develop'
```

**SENTINEL:**
```yaml
workflow_dispatch:
  inputs:
    severity_filter:
      description: 'Minimum severity to fix'
      default: 'CRITICAL'
```

**CUSTODIAN:**
```yaml
workflow_dispatch:
  inputs:
    focus:
      description: 'Focus area (dead_code, duplication, naming, complexity, all)'
      default: 'all'
```

**Impact:** Run agents on-demand with specific parameters. No waiting for scheduled time.

---

## Migration Checklist

### Step 1: Rename/Replace Workflows

```bash
# Delete old workflows
rm -f .github/workflows/performance-improver.yml
rm -f .github/workflows/security-checker.yml
rm -f .github/workflows/weekly-cleanup.yml

# Create new directory
mkdir -p .github/workflows/agents

# Copy new workflows
cp agent-turbo-performance.yml .github/workflows/agents/
cp agent-sentinel-security.yml .github/workflows/agents/
cp agent-custodian-maintenance.yml .github/workflows/agents/
```

### Step 2: Verify Secrets

Ensure `JULES_API_KEY` is set in your GitHub repo secrets:

```
Settings → Secrets and variables → Actions → New repository secret
Name: JULES_API_KEY
Value: [your Jules API key]
```

### Step 3: Test Manually

Run each workflow manually (via `workflow_dispatch`) to verify:

```
GitHub → Actions → [Agent workflow name] → Run workflow
```

### Step 4: Monitor First Schedule

After first scheduled run (next day 4 AM UTC), check:

- [ ] TURBO created a draft PR (or no PR if no opportunities)
- [ ] SENTINEL created a draft PR (or no PR if no vulnerabilities)
- [ ] CUSTODIAN created a draft PR next Monday (or no PR if no cleanups)
- [ ] All PRs have guardrails comment
- [ ] All PRs are drafts (not ready to merge)

### Step 5: Review & Adjust

After first run, review each PR and:

1. Assess quality (did agent find real issues?)
2. Check false positives (mark as closed if invalid)
3. Adjust prompts if needed (refine next time)
4. Merge if valid (or request changes)

---

## Performance Expectations

### Turbo (Performance Optimization)

**Expected Output:**
- 1 PR per week (if opportunities exist)
- Focuses on: N+1 Eloquent queries, missing eager loads, inefficient query patterns
- Impact: 5–50% query reduction on affected endpoints

**False Positive Risks:**
- Suggests eager loading without knowing relationship cost (medium risk)
- Misses architectural issues that require refactoring (expected)
- Doesn't understand domain-specific query logic (expected)

### Sentinel (Security)

**Expected Output:**
- 1–3 PRs per month (CRITICAL/HIGH issues)
- Focuses on: hardcoded secrets, mass assignment, missing validation
- Impact: Blocks actual vulnerabilities

**False Positive Risks:**
- Suggests fixes for fields already encrypted elsewhere (low risk)
- Doesn't understand multi-tenant permission scoping deeply (medium risk)
- May flag "insecure" patterns that are intentionally safe (low risk)

### Custodian (Maintenance)

**Expected Output:**
- 1 PR per month (if dead code / duplication exists)
- Focuses on: unused imports, dead variables, duplicated validation rules
- Impact: ~5–10% codebase reduction over time

**False Positive Risks:**
- Removes code that's used polymorphically (low risk with guardrails)
- Breaks hidden dependencies via Observers/Events (low risk with skip rules)
- Over-aggressive on "naming improvements" (expected, reviewable)

---

## Manual Triggers

### Run TURBO Now

```bash
gh workflow run agent-turbo-performance.yml -f target_branch=develop
```

### Run SENTINEL with HIGH+CRITICAL

```bash
gh workflow run agent-sentinel-security.yml -f severity_filter=CRITICAL
```

### Run CUSTODIAN on Naming Issues Only

```bash
gh workflow run agent-custodian-maintenance.yml -f focus=naming
```

---

## Disabling an Agent

To temporarily disable an agent (e.g., during active refactoring):

1. Open the workflow file
2. Add `if: false` to the job:
   ```yaml
   jobs:
     turbo-scan:
       if: false  # Disabled during sprint
       runs-on: ubuntu-latest
   ```
3. Commit and push
4. Remove when ready to re-enable

---

## Common Issues

### Issue: Agent creates PR but all tests fail

**Cause:** Agent made a change that broke tests.

**Fix:**
1. Close the draft PR
2. Review what agent suggested
3. Adjust the prompt to add more guardrails (e.g., "only if tests pass before/after")

### Issue: TURBO suggests eager loads that don't help

**Cause:** Agent doesn't know query cost; suggests all `with()` calls.

**Fix:**
1. Review the suggested eager loads
2. Measure actual impact (SPX profiler, Laravel Debugbar)
3. Merge if valid, close if not
4. Adjust prompt to say "only obvious N+1 patterns" (already in improved prompt)

### Issue: SENTINEL flags multi-tenant authorization as "missing auth"

**Cause:** Agent doesn't see full permission scope logic.

**Fix:**
1. Review the flag; likely false positive
2. Add comment explaining your gate logic includes tenant checks
3. Adjust prompt to be more conservative on authorization (already in improved prompt)

### Issue: Agent creates 10 PRs at once (overwhelming)

**Cause:** First run against large codebase; agent goes overboard.

**Fix:**
1. Close most of them
2. Keep only high-value PRs
3. Adjust prompt to say "pick ONE opportunity" (already in improved prompts)
4. Run weekly instead of daily (adjust cron)

---

## Next Steps

1. **Replace workflows** with new agent files
2. **Test manually** via `workflow_dispatch`
3. **Monitor first scheduled runs**
4. **Adjust prompts** based on output quality
5. **Document custom rules** (e.g., "always prefer Query scopes over Repository methods")

