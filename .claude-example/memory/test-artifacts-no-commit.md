---
name: test-artifacts-no-commit
description: "Test artifacts (screenshots, videos, reports, logs) HARUS di-ignore dari git — jangan commit"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Test Artifacts (Screenshots/Reports/Videos) JANGAN di-commit

## Context
Saat testing E2E Playwright (headed mode), setiap test nge-generate banyak file:
- `*.png` (fullPage screenshots tiap step)
- `*.webm` (video recording)
- `*.html` (HTML report)
- `*.log` (log output)
- `playwright-report/`, `test-results/`, `report/` folders

File-file ini **penuh sampah** — bukan source code. Kalau di-commit ke repo:
- Repo bengkak (bisa ratusan MB per test run)
- Deploy server download repo + waste storage
- PR diff jadi noise (screenshot diff susah di-review)

## Why
2026-06-25 saat commit Gangguan feature, saya commit 38 PNG screenshots tanpa sadar. User langsung menegur: "kalau deploy nanti kasihan servernya makan sampah". Langsung saya untrack + add `.gitignore` patterns.

## How to apply

### 1. `.gitignore` SUDAH punya patterns ini (per 2026-06-25)
```gitignore
# Test artifacts
tests/Browser/Playwright/Feature/*/screenshots/
tests/Browser/Playwright/Feature/*/screenshots-*/
tests/Browser/Playwright/Feature/**/report/
tests/Browser/Playwright/Feature/**/playwright-report/
tests/Browser/Playwright/Feature/**/test-results/
tests/Browser/Playwright/Feature/**/*.html
tests/Browser/Playwright/Feature/**/*.webm
tests/Browser/Playwright/Feature/**/*.zip
tests/Browser/Playwright/Feature/**/*.log
```

### 2. SEBELUM commit, SELALU cek
```bash
git status
```
- Kalau ada file `*.png`, `*.html`, `playwright-report/`, dll → JANGAN `git add .` 
- Pakai `git add <path-specific>` atau `git add -p` (interactive)

### 3. Kalau terlanjur ke-commit
```bash
git rm -r --cached <folder>  # untrack (file tetap di working dir)
git commit -m "chore(gitignore): exclude ..."
```

### 4. Kalau ada pattern baru (misal `videos/`, `traces/`)
- Add ke `.gitignore` 
- Untrack existing files
- Test: `git check-ignore <file>` untuk verify pattern works

### 5. Boleh di-commit (artifacts):
- ❌ PNG/JPG/WebM dari headed Playwright runs
- ❌ HTML report dari test
- ❌ JSON test result files
- ❌ Log files
- ❌ Temp file (zip, csv dump, dll)
- ✅ Test scripts (`.cjs` files) — INI source code, COMMIT
- ✅ Test fixtures (test data di DB / seeder) — boleh di-commit kalau kecil

## Related
- [teliti-workflow.md](teliti-workflow.md) — workflow umum
- [testing-with-headed-browser.md](testing-with-headed-browser.md) — pakai headed mode
