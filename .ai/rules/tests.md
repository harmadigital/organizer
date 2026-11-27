---
paths:
  - 'tests/**'
---

# Tests

## Master screens inherit MasterRecordTestCase; browser checks use a sandbox
A test for a master data screen extends Tests\Feature\Master\MasterRecordTestCase and adds only what is specific to it. Use one acting user per test, or the session guard logs the second one out. For a browser check, serve a throwaway SQLite database with php artisan serve --no-reload: without that flag the server drops shell DB_* variables and silently uses the dev database from .env.
