---
paths:
  - 'database/seeders/**'
---

# Seeders

## Keep model events enabled in DatabaseSeeder
Do not add WithoutModelEvents to DatabaseSeeder. DummyDataSeeder relies on ServiceOrderObserver (registered in AppServiceProvider) to record the status-history timeline while it walks each order through the status flow. With events disabled, no history rows are created and backdateHistory() silently no-ops.
