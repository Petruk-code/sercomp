---
paths:
  - 'app/Http/Controllers/Reports/**'
---

# Reports

## when($request->filled(...)) passes boolean true as the value
`->when($request->filled('x'), fn ($q, $v) => $q->where('col', $v))` passes the boolean `true` as the callback's second arg, so `where('col', true)` matches col=1 — a real bug fixed in ReportController::debts. Resolve the value first (`$v = $request->filled('x') ? (int) $request->integer('x') : null;`) and guard with `->when($v !== null, ...)`. Also: PHP `serialize_precision=-1` encodes whole floats as ints in JSON (json_encode(0.0) → '0'), so Inertia test assertions must compare money/buckets as ints, not `0.0`.
