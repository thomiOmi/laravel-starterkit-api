# Integration with Laravel Starter Kit & Agent Skills

Based on Thomi's context: Laravel 13 API + Nuxt v4 frontend + modular architecture (Repository/Action pattern) + agent skills library (github.com/thomiOmi/agent-skills)

---

## Part 1: GitHub Agents × Agent Skills Library

### Your Current Agent Skills Library

You're building: `github.com/thomiOmi/agent-skills`
- 12+ skills following agentskills.io spec
- PowerShell installer
- AGENTS.md with anti-sycophancy rules, confidence labeling
- Pending: CHANGELOG.md, knowledge plugin, custom agents (code-reviewer, security-auditor)

### How GitHub Workflow Agents Fit

**GitHub Workflow Agents (TURBO/SENTINEL/CUSTODIAN):**
- ✅ Autonomous, scheduled, push PRs
- ✅ No manual intervention (besides PR review)
- ✅ Integrated with GitHub Actions
- ✅ Can use git diff context

**Your Agent Skills Library:**
- ✅ Manual invocation (you run them)
- ✅ Interactive (you guide them)
- ✅ Local tools (OpenCode CLI integration)
- ✅ Stateful (remembers context within session)

### They Work Together, Not Instead

**Use workflow agents for:**
- Continuous background optimization (TURBO)
- Security monitoring (SENTINEL)
- Code hygiene (CUSTODIAN)

**Use your agent skills for:**
- Targeted refactoring (code-reviewer agent)
- Security audits (security-auditor agent)
- Complex domain changes (interactive agents)

**Example workflow:**
```
Day 1: SENTINEL agent creates "add mass assignment protection" PR
Day 2: You review + merge
Day 3: You manually run your security-auditor skill for deeper audit
Day 4: Update authorization gates based on audit findings
Day 5: TURBO agent suggests performance improvements
Day 6: You review + merge + verify measurements
```

---

## Part 2: Tailoring Agents for laravel-starterkit-api

You use `laravel-starterkit-api` as a test project for agent skills.

### TURBO Customization for Repository/Action Pattern

Add to `agent-turbo-performance.yml`:

```yaml
prompt: |
  # TURBO — Laravel Starter Kit Performance Agent
  
  Framework: Laravel 13 + Pest v4
  Architecture: Repository/Action pattern with custom Gates
  Test Framework: Pest v4
  
  ## Custom Patterns for This Codebase
  
  ### Repository Patterns (High Impact)
  - Repository methods fetching full Model when only ID needed
    Example: Repository::getUser($id) returns User with all relations
    Better: Repository::getUserId($id) returns just ID
  
  - Missing query scopes in Repository methods
    Example: Repository::getActive() without ->where('is_active', true)
    Better: Use scope or add scope inside Repository
  
  - Circular Repository calls (Repository A calls Repository B calls A)
    Flag if found (rare but high-cost)
  
  ### Action Patterns
  - Actions doing unnecessary eager loading before hydration
    Example: Action receives User model, immediately re-fetches with load()
    Better: Pass already-loaded model or accept ID
  
  - Actions with side-effect queries inside loops
    Example: Loop over Leases, each iteration calls Repository::getTenant()
    Better: Batch-load tenants before loop
  
  ### Pest Test Patterns
  - Tests using Model::factory()->create()->create() (nested creation)
    Better: Explicitly list what you're testing
  
  - Missing database transactions in tests
    Better: Use ->expectsDatabase() or transactions
  
  ### Auth/Gates (Bearer Token + Custom Gates)
  - Repeated auth()->user() calls in same request
    Better: Cache in request variable
  
  - Gate checks without cove scope
    Example: Gate doesn't verify tenant isolation
    Check: Does gate include ->scoped() or explicit cove check?
  
  ## Rules
  - Only if test coverage exists in changed file
  - Changes ≤50 lines
  - If touches Gate definitions, verify cove scope
  - Don't optimize queries that are intentionally simple for clarity
  
  [rest of TURBO prompt]
```

### SENTINEL Customization for Starter Kit

Add to `agent-sentinel-security.yml`:

```yaml
prompt: |
  # SENTINEL — Laravel Starter Kit Security Agent
  
  Framework: Laravel 13 + Sanctum (Bearer tokens)
  Architecture: Repository/Action pattern
  Permission System: Spatie Permission + custom Gates with cove scope
  Auth Method: Bearer tokens (API-only, no cookies)
  
  ## Starter Kit Specific Patterns
  
  ### Auth & Gates
  - Missing cove scope in Gate definitions
    Bad: Gate::define('edit-lease', fn($user) => $user->can('edit-lease'))
    Good: Gate::define('edit-lease', fn($user, $lease) => $user->cove_id === $lease->cove_id && $user->can(...))
  
  - Bearer token in code or logs
    Flag immediately (CRITICAL)
  
  - Sanctum token endpoint without rate limiting
    Check: Has rate limiting middleware
  
  ### API Routes & FormRequests
  - API routes without validation
    Example: Route::post('/leases', fn() => Lease::create(request()->all()))
    Better: Use FormRequest with validation
  
  - FormRequest without explicit $guarded/$fillable
    Flag: Could allow mass assignment of sensitive fields
  
  - API responses returning sensitive data (passwords, tokens)
    Flag: Audit what's being returned
  
  ### Repository Pattern
  - Repository methods exposing internal query logic
    Example: Public method returns raw Query builder
    Better: Return model or specific data only
  
  - Repository methods without authorization checks
    Check: Does it verify user can access this data for their cove?
  
  ### Action Pattern
  - Actions accepting user input without validation
    Example: Action(array $data) with no validation
    Better: Action(FormRequest $request) or validated data
  
  - Actions creating/updating models without checking ownership
    Example: Lease::update($data) without verifying current cove
    Better: Lease::findByIdInCove($id, $cove)->update($data)
  
  ### Multi-Tenant Isolation
  - Queries missing cove_id filter
    Pattern: Lease::where(...) without ->where('cove_id', auth()->user()->cove_id)
    Better: Use scope: Lease::inCove(auth()->user()->cove)->where(...)
  
  - Relationships allowing cross-cove access
    Example: Lease::with('tenant') without tenant being scoped to cove
    Better: Ensure Tenant has scope for cove isolation
  
  ## Critical Flags
  - Hardcoded secrets (CRITICAL)
  - SQL injection in Repository/Action (CRITICAL)
  - Missing cove scope on sensitive operations (HIGH)
  - Mass assignment on security-sensitive fields (HIGH)
  
  [rest of SENTINEL prompt]
```

### CUSTODIAN Customization for Starter Kit

Add to `agent-custodian-maintenance.yml`:

```yaml
prompt: |
  # CUSTODIAN — Laravel Starter Kit Maintenance Agent
  
  Architecture: Repository/Action pattern
  Tests: Pest v4
  
  ## Starter Kit Specific Patterns
  
  ### Repository Cleanup
  - Unused Repository methods
    Skip if: Method is part of public interface or used by other modules
  
  - Repository methods that are just thin wrappers
    Example: Repository::getLeases() → Lease::all()
    Suggestion: Remove if simpler to use Lease directly
  
  - Duplicate Repository methods in different classes
    Suggestion: Extract to shared base or interface
  
  ### Action Cleanup
  - Actions with no actual logic (just call Model::create())
    Suggestion: Consider if Action is needed; might be over-abstraction
  
  - Actions never called (check recent commits)
    Suggestion: Remove if confirmed unused
  
  ### Gate/Permission Cleanup
  - Unused Gate definitions
    Suggestion: Remove if not referenced
  
  - Hardcoded role/permission names (instead of constants)
    Suggestion: Extract to RoleEnum or PermissionEnum
  
  ### Test Cleanup
  - Unused test methods (starting with skipped methods)
    Suggestion: Remove old tests
  
  - Duplicated test logic
    Suggestion: Extract to TestCase method or trait
  
  - Test factories with old/unused fields
    Suggestion: Clean up factory definitions
  
  ### Naming Improvements
  - Repository methods with vague names
    Bad: getItems(), getLeaseData()
    Good: getActiveLeases(), getLeaseWithAddons()
  
  - Variables with unclear scope
    Bad: $data, $result, $model
    Good: $leaseData, $savedLease, $targetModel
  
  ### Module Organization
  - Unused imports (especially Repositories/Actions)
  
  - Classes in wrong namespace
    Example: App\Services\LeaseService (should be App\Actions\UpdateLeaseAction)
  
  ## Rules
  - No removal without test coverage
  - Don't touch middleware, providers, config
  - Don't break public API contracts
  - If unsure, skip (repository pattern is intentional abstraction)
  
  [rest of CUSTODIAN prompt]
```

---

## Part 3: Integration with Your Agent Skills Library

### Shared Conventions

Both your workflow agents and agent skills should follow AGENTS.md conventions:

**Current AGENTS.md approach:**
```
- Anti-sycophancy rules (don't just agree)
- Confidence labeling (how sure are you?)
- Tool mandates (which tools must be used)
```

**Extend this to workflow agents:**

Create `.github/workflows/agents/AGENTS.md`:
```markdown
# GitHub Workflow Agent Rules

## Anti-Sycophancy
- ❌ "This is a good idea" (empty validation)
- ✅ "This change reduces queries from 5 to 1, verified by test execution"

## Confidence Labeling
- TURBO: Label confidence (>90%, >70%, <70%)
- SENTINEL: Label severity (CRITICAL, HIGH, MEDIUM)
- CUSTODIAN: Label risk level (safe, review needed, high risk)

## Tool Mandates
- TURBO: Must use Pest test runner
- SENTINEL: Must check Spatie Permission structure
- CUSTODIAN: Must respect Repository/Action pattern

## Codebase-Specific Rules
- All queries must include cove_id where applicable
- All Actions must accept validated FormRequest or array
- All Repositories must use scopes, not raw query builders
```

---

### Using Workflow Agents to Improve Agent Skills

The GitHub agents can improve your agent skills library:

```
Your agent-skills repo structure:
├── skills/
│   ├── api-skill/
│   ├── security-skill/
│   └── ...
├── agents/
│   ├── code-reviewer/
│   ├── security-auditor/
│   └── ...
├── AGENTS.md
├── CHANGELOG.md
└── .github/workflows/agents/  # ← Deploy workflow agents here!
    ├── agent-turbo-performance.yml
    ├── agent-sentinel-security.yml
    └── agent-custodian-maintenance.yml
```

**Benefits:**
- TURBO optimizes your agent skills code
- SENTINEL finds security issues in your agent code
- CUSTODIAN removes dead code from your skills

**Customize for your skills repo:**

```yaml
# .github/workflows/agents/agent-turbo-performance.yml
prompt: |
  # TURBO — Agent Skills Library Performance
  
  Focus areas:
  - Agent skills with inefficient patterns
  - Prompt generation code that could be optimized
  - Unnecessary context gathering (bloats prompt size)
  
  Patterns to look for:
  - Agent skills running unnecessary API calls
  - Prompts with redundant examples
  - Knowledge gathering doing N+1 across files
  
  [rest]
```

---

## Part 4: Real-World Example: laravel-starterkit-api

### Scenario: First Month of Agents

**Week 1:**
```
TURBO runs:
- Suggests eager loading on Lease model in Repository
- "Reduces queries from 5 to 1 in list endpoint"
- You verify: Actually 4→1 (agent overestimated)
- Merge anyway (still useful)

SENTINEL runs:
- Flags hardcoded API key in test factory
- "Use Str::random() instead"
- You merge immediately (obvious fix)
- Also flags: FormRequest without validation on sensitive field
- You review, add validation rule

CUSTODIAN runs:
- Suggests removing unused import in Action
- Renames $data → $leaseData for clarity
- You merge (low-risk changes)
```

**Week 2:**
```
TURBO runs:
- Suggests caching auth()->user() in middleware
- "Reduces repeated lookups from 3 to 1 per request"
- You measure: Real impact is ~2ms per request
- Not worth the complexity; close PR

SENTINEL runs:
- Flags missing cove_id scope in relationship
- Repository method: Lease::with('resident')
- Should be: Lease::whereCoveId($id)->with('resident')
- You fix and merge (real security issue)

CUSTODIAN runs:
- No changes suggested this week
```

**Week 3:**
```
You run your custom security-auditor skill (manual):
- Deeper audit of permission gates
- Suggests restructuring authorization logic
- More complex than agent can handle
- You make changes, then TURBO/SENTINEL verify next week
```

---

### Expected Impact After 3 Months

**Performance (TURBO):**
- 10–20 merged PRs
- Estimated 15–30% query reduction on API endpoints
- Server cost savings: ~$50–200/month

**Security (SENTINEL):**
- 5–10 vulnerabilities fixed (mostly obvious: hardcoded secrets, missing validation)
- Confidence: High (these are real issues)
- Remaining vulnerabilities: Require manual security audits

**Maintenance (CUSTODIAN):**
- 15–25 merged PRs
- ~100–200 lines of dead code removed
- Improved code clarity (better naming)
- Technical debt reduced

---

## Part 5: Workflow Agent Integration with Jules Agent

### Local Development: Jules on Your Machine

You're using Jules (Google's CLI agent). How agents interact:

```
┌─────────────────────────────────────────┐
│ GitHub Workflow Agents (Automatic)      │
│ - TURBO/SENTINEL/CUSTODIAN              │
│ - Scheduled, autonomous                 │
│ - PRs appear daily/weekly                │
└─────────────────────────────────────────┘
           ↓ (You review & merge)
┌─────────────────────────────────────────┐
│ Local Development with Jules            │
│ - You guide agents                      │
│ - Interactive, real-time                │
│ - Complex refactoring                   │
└─────────────────────────────────────────┘
           ↓ (You commit changes)
┌─────────────────────────────────────────┐
│ GitHub Workflow Agents See Changes      │
│ - Next scheduled run                    │
│ - Verifies improvements                 │
│ - Suggests further optimizations        │
└─────────────────────────────────────────┘
```

**Example collaborative session:**

```
You (locally): "Jules, refactor this Repository method to use Action pattern"
Jules: [Makes refactoring locally]
You: [Test locally, commit]

Next morning:
TURBO agent: "Found N+1 in new Action code; suggest eager load"
You: [Review TURBO PR, merge if valid]

Later that week:
SENTINEL agent: [Verifies new Action includes authorization checks]
You: [Review, merge or request changes]
```

---

### Your Agent Skills + Workflow Agents

**Your agent-reviewer skill (custom):**
- Local: You invoke with `opencode agent-reviewer <file>`
- Reviews code for your architecture
- Suggests Repository/Action improvements

**CUSTODIAN workflow agent:**
- Automatic: Runs daily
- Reviews entire codebase for dead code
- Suggests extracted patterns

**They complement each other:**
```
agent-reviewer (manual):
- Deep understanding of your decisions
- Checks against your patterns
- Interactive feedback

CUSTODIAN (automatic):
- Catches obvious dead code
- Removes boilerplate
- No context needed
```

---

## Part 6: Checklist for Using Agents with Starter Kit

### Before Deploying Agents

- [ ] Copy workflow files to `.github/workflows/agents/`
- [ ] Set `JULES_API_KEY` secret
- [ ] Customize prompts for your Repository/Action pattern
- [ ] Add multi-tenant/cove scoping notes to prompts
- [ ] Verify Bearer token auth context is in SENTINEL prompt

### First Week of Monitoring

- [ ] TURBO creates 2–3 PRs (performance opportunities)
- [ ] SENTINEL finds 1–2 PRs (security issues)
- [ ] CUSTODIAN creates 2–3 PRs (code quality)
- [ ] Review all PRs; note any false positives

### First Month Adjustments

- [ ] Update prompts based on false positives
- [ ] Increase TURBO measurement accuracy
- [ ] Strengthen SENTINEL multi-tenant awareness
- [ ] Document team preferences (AGENTS.md)

### Integration with Agent Skills

- [ ] Decide: Which agents run automatically (workflow)?
- [ ] Decide: Which require manual invocation (skills)?
- [ ] Document in AGENTS.md the division of labor
- [ ] Set up feedback loop (workflow PR → manual audit → improvements)

---

## Part 7: Cost Analysis for Starter Kit

### API Calls per Month

```
3 agents × 1 call/day (on average) × 30 days = 90 API calls/month
90 calls × $0.01–0.10 per call (estimate) = $0.90–9.00/month

Realistic estimate: $2–5/month for starter kit repo
```

### ROI

**TURBO (Performance):**
- Estimated 20% query reduction
- Server cost: ~$10/month → saves ~$2/month
- Plus developer time saved on manual optimization: ~5–10 hours/month

**SENTINEL (Security):**
- Catches hardcoded secrets, missing validation
- Prevents one security incident: $5000–50000+ avoided risk
- Plus regulatory/compliance requirements

**CUSTODIAN (Maintenance):**
- Reduces technical debt
- Faster onboarding for new developers
- Estimated 10–20 hours/month saved

**Total ROI:** Positive within first month.

---

## Part 8: Troubleshooting Specific to Starter Kit

### Issue: Agent suggests removing Repository method that's actually used

**Root cause:** Method is used only in tests or via macro.

**Fix:**
```php
// Add context comment for agent
/**
 * Get tenants for current cove.
 * 
 * Used by: Leases controller pivot loading
 * Do not remove: Required for API contract
 */
public function getTenantsInCove($coveId) {
  return Tenant::whereCoveId($coveId)->get();
}
```

---

### Issue: Agent suggests Action that overlaps with Repository

**Root cause:** Agent doesn't understand your architectural boundary.

**Current approach:**
- Repository: Data access layer (queries)
- Action: Business logic layer (domain logic)

**Example conflict:**
```php
// CUSTODIAN suggests merging these:
Repository::getActiveLeases()  // ← Query layer
Action::getActiveLeasesForDisplay()  // ← Business logic

// They have different purposes! Don't merge.
```

**Fix:** Add to CUSTODIAN prompt:
```
Repository: Pure data access, returns models
Action: Business logic, may call multiple Repositories
Never merge Repository method into Action or vice versa.
```

---

### Issue: Tests fail after agent changes but locally tests pass

**Root cause:** Environment mismatch (your local SQLite vs CI MySQL).

**Solution:**
```bash
# Use same database as CI
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_DATABASE=test_app

# Run tests locally
vendor/bin/pest

# Then commit agent changes
```

---

## Part 9: Next Steps

### Immediate (This Week)

1. Copy workflow files to `.github/workflows/agents/`
2. Set `JULES_API_KEY` secret
3. Customize prompts for Repository/Action pattern
4. Test manually (run each workflow once)

### Short-term (This Month)

1. Monitor first scheduled runs
2. Review all agent PRs
3. Track which suggestions are valuable
4. Update AGENTS.md with learnings

### Medium-term (Next Quarter)

1. Integrate agents with your agent-skills repo
2. Create custom skills based on agent patterns
3. Build code-reviewer and security-auditor agents
4. Document team conventions for agents

---

## Conclusion

**Workflow agents complement your agent skills library:**

- **Workflow Agents:** Autonomous, scheduled, low-effort maintenance
- **Agent Skills:** Manual, interactive, high-effort refactoring

**For laravel-starterkit-api:**

- Deploy workflow agents for continuous improvement
- Use agent skills for targeted, complex refactoring
- Let them feed each other: workflow PRs inspire skill improvements
- Track ROI: code quality metrics, security issues fixed, developer time saved

Good luck! 🚀

