---
paths:
  - 'resources/js/**'
---

# Js

## Wayfinder multi-param actions take a single tuple/object arg
Generated Wayfinder actions with multiple route params (e.g. ServiceOrderController.removeItem for /services/{service}/items/{item}) accept ONE argument: either an object `{ service, item }` or a tuple `[service, item]`. Do not call `.form(a, b)` or `.delete(a, b)` with rest args — the second arg is treated as options. Use `.form([serviceId, itemId])`.
