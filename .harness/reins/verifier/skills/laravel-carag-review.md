# Skill: Code Reviewer - Carag V2

## Overview

This skill equips the verifier agent to review code changes for the Carag V2 project.

## Review Principles

### 1. Always Verify Behavior, Not Just Code

```php
// ❌ Don't just check if code compiles
// ✅ Actually run and verify behavior
$response = $this->get('/app/work-orders');
$response->assertStatus(200);
```

### 2. Check for These Issues

#### N+1 Query Problems
```php
// ❌ N+1 detected
$workOrders->each(function ($wo) {
    echo $wo->customer->name; // Lazy loading in loop
});

// ✅ Fix verified
$workOrders = WorkOrder::with('customer')->get();
```

#### Missing Authorization
```php
// ❌ Missing check
public function destroy(WorkOrder $workOrder)
{
    $workOrder->delete(); // No authorize!
}

// ✅ Verified
public function destroy(WorkOrder $workOrder)
{
    $this->authorize('delete', $workOrder);
    $workOrder->delete();
}
```

#### Multi-Tenancy Violations
```php
// ❌ Missing tenant scope
$customers = Customer::all(); // No tenant_id filter!

// ✅ Verified
$customers = Customer::where('tenant_id', auth()->user()->tenant_id)->get();
```

#### Magic Numbers/Strings
```php
// ❌ Magic string
$query->whereIn('status', ['open', 'in_progress', 'on_hold']);

// ✅ Use constants
$query->whereIn('status', WorkOrder::OPEN_STATUSES);
```

### 3. Security Checks

- [ ] SQL Injection protection (use Eloquent, not raw SQL)
- [ ] XSS protection (sanitize output in Vue)
- [ ] CSRF protection (Laravel handles this)
- [ ] Authorization on all endpoints
- [ ] Input validation (Form Requests)
- [ ] File upload security
- [ ] Tenant isolation

### 4. Performance Checks

- [ ] Eager loading used for relationships
- [ ] Database indexes exist for filtered columns
- [ ] No N+1 queries in loops
- [ ] Pagination used for large datasets
- [ ] No unnecessary queries in view

### 5. Code Quality Checks

- [ ] SOLID principles followed
- [ ] No duplicated code (use traits/services/actions)
- [ ] Proper error handling
- [ ] Activity logging for important actions
- [ ] Constants used instead of magic values
- [ ] PHPDoc comments for complex methods

## Verification Process

### Step 1: Read Changed Files
```bash
# Get list of changed files
git diff --name-only HEAD~1

# Read each file
cat app/Http/Controllers/App/WorkOrderController.php
```

### Step 2: Run Tests
```bash
# Run related tests
php artisan test --filter=WorkOrderTest

# Run entire test suite
php artisan test
```

### Step 3: Check Edge Cases

Test these scenarios:
1. Empty inputs
2. Invalid data
3. Permission denied
4. Tenant isolation
5. Concurrent access

### Step 4: Document Findings

Create a verification report:

```markdown
## Verification Report

### ✅ Passed Checks
- Authorization present
- Multi-tenancy enforced
- Tests pass

### ⚠️ Issues Found
- **N+1 Query** in `index()` method, line 45
  - Change: `$customers->each(...)` to `Customer::with('vehicles')->get()`
  - Impact: High - causes 100+ queries on large datasets

### 📋 Recommendations
1. Add database index on `customers.tenant_id`
2. Extract duplicate query logic to a scope

### Verdict
PASS / FAIL / NEEDS_CHANGES
```

## Common Patterns to Verify

### Controllers
- [ ] Uses `AuthorizesRequests` trait
- [ ] Has authorization checks (authorize())
- [ ] Uses Form Requests for validation
- [ ] Has proper return types
- [ ] Logs important activities

### Models
- [ ] Uses `BelongsToTenant` trait
- [ ] Has proper relationships defined
- [ ] Has scopes for common queries
- [ ] Uses constants for status/values
- [ ] Has fillable/hidden arrays

### Services
- [ ] Single responsibility
- [ ] Dependency injection used
- [ ] Returns typed values
- [ ] Handles errors gracefully

### Tests
- [ ] Tests cover happy path
- [ ] Tests cover error cases
- [ ] Tests verify authorization
- [ ] Tests verify tenant isolation
- [ ] Uses factory/state for test data

## File Review Checklist

### app/Http/Controllers/App/*.php
- [ ] Authorization present on all methods
- [ ] Uses Form Requests
- [ ] Returns proper response types
- [ ] Has activity logging
- [ ] No magic strings/numbers

### app/Models/*.php
- [ ] Has tenant_id in fillable
- [ ] Relationships defined correctly
- [ ] Scopes for common queries
- [ ] Constants for statuses

### app/Services/*.php
- [ ] Single responsibility
- [ ] Properly typed
- [ ] Handles edge cases

### routes/web.php
- [ ] Proper middleware on routes
- [ ] Resource routes follow conventions
- [ ] Named routes properly

## Deliverable

After verification, write findings to `deliverable.md`:

```markdown
# Verification Report - [Task Name]

## Summary
[One paragraph overview]

## Issues Found
### High Priority
- [Issue description with file:line]

### Medium Priority
- [Issue description]

### Low Priority
- [Issue description]

## Recommendations
[Actionable recommendations]

## Verdict
[ PASS | FAIL | NEEDS_CHANGES ]

## Evidence
[Links to relevant files, test output, etc.]
```

---

*Created: 2026-05-31*