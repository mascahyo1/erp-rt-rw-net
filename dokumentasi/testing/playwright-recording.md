# Playwright Video Recording

## Install

```bash
npm install playwright
npx playwright install chromium
```

## Script Recording

File: `tests/Browser/playwright-bulk-delete.js`

### Features:
- Native video recording (WebM → MP4)
- Screenshot di setiap step
- Console log capture
- UUID untuk unique data
- Error handling
- Git ignore otomatis

### Running:

```powershell
node tests/Browser/playwright-bulk-delete.js
```

### Output:
- Video: `tests/Browser/videos/playwright/*.mp4`
- Screenshots: `tests/Browser/videos/playwright/*.png`

## Keunggulan dari Dusk:

| Feature | Playwright | Dusk |
|---------|-----------|------|
| Video Recording | Native, 60fps | Custom, buggy |
| Stabilitas | ✅ Stable | ⚠️ Sering crash |
| API | Modern JS | PHP |
| Browser Support | Chrome, Firefox, Safari | Chrome only |
| Setup | Mudah | Kompleks |

## Contoh Selector Katalon → Playwright:

```javascript
// Katalon
click('button:has-text("Cari Perusahaan")')

// Playwright
await page.click('button:has-text("Cari Perusahaan")')
```

## Tips

1. Gunakan UUID untuk data unik
2. Screenshot di setiap step untuk debugging
3. Console log capture untuk catch errors
4. Check modal visible state sebelum lanjut