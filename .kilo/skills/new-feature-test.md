# New Feature = Always Browser Test + Screenshot

When ANY new feature is added (backend controller method, route, Vue component, button, modal, etc.),
ALWAYS include a Dusk browser test with screenshots.

## Mandatory checklist per new feature:

1. **Test file** — create or append to `tests/Browser/Feature/{Guard}/{Feature}Test.php`
2. **Screenshots** — minimum 2 per test case (before + after), saved under:
   `tests/Browser/screenshots/{guard}/{feature}/{test_case_name}/`
3. **Naming** — `01-before.png`, `02-after.png`, `03-result.png`
4. **Assertions** — never leave a test without assertions (risky)
5. **loginAs()** — use auth guard matching the feature's guard

## Run the test:
```powershell
php artisan setup --demo                          # seed first
.\parallel-dusk.ps1                               # run all
# or single:
Move-Item public\hot public\hot.bak -Force
php artisan dusk --filter="FeatureName"
Move-Item public\hot.bak public\hot -Force
```

## Never:
- Add backend code without a matching Dusk test
- Add a Dusk test without screenshots
- Leave a test without assertions (risky = not counted)
