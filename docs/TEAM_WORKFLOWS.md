# Team Workflows & Integration Guide

## Recommended Team Policies

### Policy 1: Agent PR Review SLA

Establish clear expectations for reviewing agent-generated PRs:

```markdown
# Agent PR Review Policy

## Turbo (Performance) PRs
- **SLA:** Review within 2 business days
- **Approval:** Code reviewer + performance engineer (if large impact)
- **Measurement Required:** Yes (before/after metrics mandatory)
- **Auto-merge:** No
- **Merge Criteria:** 
  - Tests pass
  - Performance metric verified
  - No Observers/Events touched
  - ≤50 line changes

## Sentinel (Security) PRs
- **SLA:** Review within 1 business day (CRITICAL) / 3 days (HIGH/MEDIUM)
- **Approval:** Security engineer or senior developer
- **Auto-merge:** Only for CRITICAL hardcoded secret removal
- **Merge Criteria:**
  - Tests pass
  - No authorization logic breaks
  - ≤30 line changes
  - Fix is the recommended approach (not just one option)

## Custodian (Maintenance) PRs
- **SLA:** Review within 5 business days
- **Approval:** Any developer
- **Auto-merge:** Yes (if all tests pass)
- **Merge Criteria:**
  - Tests pass
  - Dead code removal (no implicit dependencies)
  - ≤100 line changes
  - Naming changes are conventional

## Escalation
- If SLA missed: Notify #engineering-leads
- If multiple PRs stale: Disable agent temporarily, review process
```

---

### Policy 2: Agent Output Notifications

Keep team informed without overwhelming:

```yaml
# .github/workflows/agents/notify-team.yml
name: Notify Team of Agent PRs

on:
  workflow_run:
    workflows: [Agent TURBO, Agent SENTINEL, Agent CUSTODIAN]
    types: [completed]

jobs:
  notify:
    if: github.event.workflow_run.conclusion == 'success'
    runs-on: ubuntu-latest
    steps:
      - name: Send to Slack (if PR created)
        uses: slackapi/slack-github-action@v1
        with:
          webhook-url: ${{ secrets.SLACK_ENGINEERING_WEBHOOK }}
          payload: |
            {
              "text": "🤖 Agent PR Created",
              "blocks": [
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "*${{ github.event.workflow_run.name }}*\nNew opportunity found →\n<${{ github.server_url }}/${{ github.repository }}/pulls|View PR>"
                  }
                }
              ]
            }

      - name: Send weekly summary (Mondays)
        if: github.event.schedule == '0 9 * * 1'  # Monday 9 AM
        uses: slackapi/slack-github-action@v1
        with:
          webhook-url: ${{ secrets.SLACK_ENGINEERING_WEBHOOK }}
          payload: |
            {
              "text": "📊 Weekly Agent Summary",
              "blocks": [
                {
                  "type": "section",
                  "text": {
                    "type": "mrkdwn",
                    "text": "*This Week's Agent Activity*\n• TURBO: 2 PRs (1 merged)\n• SENTINEL: 1 PR (merged)\n• CUSTODIAN: 3 PRs (2 merged)\nTotal: 6 PRs → 4 merged (67% merge rate)"
                  }
                }
              ]
            }
```

---

## Team Workflow Examples

### Scenario 1: Distributed Team (Async Review)

**Problem:** Team is across 3 time zones. Agent PRs pile up.

**Solution:**

```yaml
# Modify workflows to include assignment

- name: Assign to on-call developer
  uses: actions/github-script@v7
  with:
    script: |
      const day = new Date().getDay();
      const oncall = {
        0: 'alice',    // Sunday = alice (Monday prep)
        1: 'bob',      // Monday = bob
        2: 'charlie',  // Tuesday = charlie
        3: 'david',    // Wednesday = david
        4: 'emma',     // Thursday = emma
        5: 'frank',    // Friday = frank
        6: 'alice'     // Saturday = alice
      };
      
      github.rest.issues.addAssignees({
        owner: context.repo.owner,
        repo: context.repo.repo,
        issue_number: context.issue.number,
        assignees: [oncall[day]]
      });

- name: Add SLA label for tracking
  uses: actions/github-script@v7
  with:
    script: |
      github.rest.issues.addLabels({
        owner: context.repo.owner,
        repo: context.repo.repo,
        issue_number: context.issue.number,
        labels: ['needs-review', `review-sla-${{ env.SLA_DAYS }}-days`]
      });
```

**Benefit:** Clear ownership, no duplicate reviews.

---

### Scenario 2: High-Volume Agent PRs (Too Many)

**Problem:** Agent creates 5+ PRs per day. Team can't keep up.

**Solution 1: Reduce frequency**

```yaml
# Run once per week instead of daily
on:
  schedule:
    - cron: '0 4 * * 0'  # Sundays only
```

**Solution 2: Prioritize by impact**

```yaml
- name: Jules Prioritization Layer
  uses: google-labs-code/jules-action@v1.0.0
  with:
    prompt: |
      [agent prompt]
      
      IMPORTANT: Only create PR if opportunity meets ALL criteria:
      1. Measurable impact (TURBO) or actual vulnerability (SENTINEL)
      2. ≤X lines changed (TURBO: 30, SENTINEL: 20, CUSTODIAN: 50)
      3. High confidence (>90% chance of being correct)
      
      If multiple opportunities exist, pick ONLY the highest-impact one.
      Do NOT create multiple PRs.
      
      If no high-confidence opportunity, don't create a PR.
```

**Solution 3: Batch similar PRs**

```yaml
- name: Group Similar Changes
  uses: actions/github-script@v7
  with:
    script: |
      // Find open agent PRs
      const prs = await github.rest.pulls.list({
        owner: context.repo.owner,
        repo: context.repo.repo,
        state: 'open',
        labels: 'agent-custodian'
      });
      
      // If >3 open CUSTODIAN PRs, close new one
      if (prs.data.length > 3) {
        console.log('Too many open agent PRs. Skipping this run.');
        process.exit(0);
      }
```

---

### Scenario 3: Agent Makes Mistakes (Quality Issues)

**Problem:** Agent keeps suggesting false positives.

**Solution: Feedback Loop**

```yaml
# Track which agent PRs are closed without merge

- name: Capture PR Close Reason
  if: github.event.action == 'closed'
  uses: actions/github-script@v7
  with:
    script: |
      // Only if PR wasn't merged
      if (!context.payload.pull_request.merged) {
        github.rest.issues.createComment({
          owner: context.repo.owner,
          repo: context.repo.repo,
          issue_number: context.issue.number,
          body: `## ❌ Why Was This PR Closed?\n\nHelp improve the agent:\n- [ ] False positive (agent misunderstood something)\n- [ ] Not beneficial (correct but no real value)\n- [ ] Breaks something (caused unexpected issue)\n- [ ] Wrong approach (different solution preferred)\n\nLeave a comment explaining for the next iteration.`
        });
      }
```

Then analyze:

```bash
# Count false positives per agent
gh pr list --state=closed --search="label:agent-turbo" --json=body | \
  jq '.[] | select(.body | contains("False positive"))' | wc -l
```

---

## Integration with CI/CD Pipeline

### Pre-Deploy Agent Validation

Run agents in pre-deployment environment:

```yaml
# .github/workflows/agents/validate-before-deploy.yml

on:
  pull_request:
    types: [opened, synchronize]
    paths:
      - 'app/**'
      - 'config/**'

jobs:
  validate:
    if: github.event.pull_request.author_association != 'CONTRIBUTOR'
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      
      - name: Run all tests (pre-deployment check)
        run: vendor/bin/pest --no-interaction
      
      - name: Check for security issues (pre-deploy)
        run: |
          # Custom security scanning beyond agent
          vendor/bin/phpstan analyse
          # Your custom security checks
      
      - name: Comment: Safe to Deploy
        if: success()
        uses: actions/github-script@v7
        with:
          script: |
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              body: '✅ Pre-deployment validation passed. Safe to deploy to staging.'
            });
```

---

### Post-Deploy Agent Monitoring

Run agents on production metrics:

```yaml
# .github/workflows/agents/post-deploy-validation.yml

on:
  workflow_run:
    workflows: [Deploy to Production]
    types: [completed]

jobs:
  monitor:
    if: github.event.workflow_run.conclusion == 'success'
    runs-on: ubuntu-latest
    steps:
      - name: Check error rates post-deploy
        run: |
          # Pull metrics from APM (DataDog, New Relic, etc)
          ERROR_RATE=$(curl -s $APM_ENDPOINT | jq '.error_rate')
          
          if [ "$ERROR_RATE" -gt "0.5" ]; then
            echo "High error rate post-deploy!"
            # Trigger rollback or alert
          fi
      
      - name: Notify #incidents if issues
        if: failure()
        uses: slackapi/slack-github-action@v1
        with:
          webhook-url: ${{ secrets.SLACK_INCIDENTS_WEBHOOK }}
          payload: |
            {
              "text": "⚠️ Post-deploy metrics show issues. Agent suggestions may have unintended impact."
            }
```

---

## Documentation for Developers

Create a simple guide in your team docs:

### README.md for Agent PRs

```markdown
# About Agent-Generated PRs

This repository uses three autonomous agents to improve code:

## 🚀 TURBO — Performance Optimizer
- **Runs:** Daily at 4 AM UTC
- **Looks for:** N+1 queries, missing eager loads, inefficient patterns
- **When to merge:** Performance metric verified, tests pass, ≤50 line change

## 🔒 SENTINEL — Security Scanner
- **Runs:** Daily at 5 AM UTC
- **Looks for:** Hardcoded secrets, missing validation, mass assignment holes
- **When to merge:** CRITICAL fixes (auto-approve), HIGH/MEDIUM need review

## ♻️ CUSTODIAN — Code Quality
- **Runs:** Weekly Monday 2 AM UTC
- **Looks for:** Dead code, duplication, poor naming
- **When to merge:** If tests pass (low risk)

### What to Do When You See an Agent PR

1. **Read the PR description** — explains what was found
2. **Check the guardrails comment** — lists what was verified
3. **Review the diff** — see the actual changes
4. **Run tests locally** (optional but recommended)
5. **Approve or close**

### Safe Merges

✅ **Always safe:**
- SENTINEL CRITICAL fixes
- CUSTODIAN dead code removal (unused imports, variables)
- TURBO if you verify the performance metric

⚠️ **Requires review:**
- TURBO eager loading (could cause N+1 in different code path)
- SENTINEL HIGH/MEDIUM (might have side effects)
- CUSTODIAN duplicated logic (might be intentional)

### When to Close (Don't Merge)

- ❌ Performance metric is not measurable
- ❌ Tests fail after agent's change
- ❌ Security fix breaks existing functionality
- ❌ Removal touches Observer, Listener, or Event
- ❌ Extraction introduces coupling

### Questions?

Post in #engineering or tag @automation-team

---

## Agent Configuration

Location: `.github/workflows/agents/`

- `agent-turbo-performance.yml` — Runs performance optimization
- `agent-sentinel-security.yml` — Runs security scanning
- `agent-custodian-maintenance.yml` — Runs code quality

To disable: Add `if: false` to job and push.
To adjust: Edit the prompt in the workflow file.
```

---

## Example Dashboard (GitHub Projects)

Use GitHub Projects to track agent PRs:

```
Project Name: "Agent Improvements"

Columns:
├── 🔄 New (Just created by agent)
├── 👀 Review (Assigned to dev)
├── ✅ Approved (Ready to merge)
├── 🔀 Merged (Successfully shipped)
└── ❌ Rejected (Closed without merge)

Rules:
- Auto-move to "New" when agent PR opened
- Auto-move to "Merged" when PR merged
- Alert via Slack if "Review" column has >2 weeks old PRs
```

---

## Monthly Retrospective

Track agent effectiveness with a simple spreadsheet:

```
| Month | TURBO PRs | TURBO Merged | SENTINEL PRs | SENTINEL Merged | CUSTODIAN PRs | CUSTODIAN Merged |
|-------|-----------|--------------|--------------|-----------------|---------------|------------------|
| Jan   | 4         | 2 (50%)      | 1            | 1 (100%)        | 3             | 2 (67%)          |
| Feb   | 5         | 4 (80%)      | 2            | 2 (100%)        | 2             | 2 (100%)         |
| Mar   | 3         | 3 (100%)     | 0            | 0               | 4             | 3 (75%)          |

Metrics:
- Merge rate (%) — Higher is better (less noise)
- Time-to-merge (days) — Lower is better
- Impact (estimated hours saved) — Higher is better
```

**Discussion points:**
- Why did TURBO merge rate drop in Feb?
- Is SENTINEL finding real issues or false positives?
- Are CUSTODIAN PRs valuable or just noise?
- Should we adjust frequency/thresholds?

