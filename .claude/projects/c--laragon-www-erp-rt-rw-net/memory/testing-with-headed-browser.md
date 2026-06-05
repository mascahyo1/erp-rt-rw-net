---
name: testing-with-headed-browser
description: Aturan testing Playwright — pakai headed browser (non-headless) untuk debugging, bukan headless:true
metadata:
  type: feedback
---

Saat run Playwright test untuk debugging visual (logo tidak muncul, UI tidak sesuai, dll), SELALU pakai `headless: false` (atau omit) — JANGAN `headless: true`. User ingin lihat browser muncul supaya bisa verify sendiri.

**Why:** User tidak bisa verify test result kalau browser tidak kelihatan. Headless mode = silent, user cuma lihat log. Headed mode = user bisa inspect, screenshot, bahkan interact kalau perlu.

**How to apply:**
- Default `chromium.launch({ headless: false })` untuk semua test debugging visual
- Hanya pakai `headless: true` untuk CI/automated test yang tidak perlu visual
- Set `slowMo: 500` kalau perlu biar user bisa lihat animasi
- Saat run test, ensure browser window muncul di desktop user
- Kalau test ada step yang perlu diverify (mis. logo appearance, modal behavior), pakai `page.pause()` atau screenshot manual
