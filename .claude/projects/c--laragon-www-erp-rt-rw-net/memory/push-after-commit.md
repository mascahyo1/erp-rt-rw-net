---
name: push-after-commit
description: SELALU push ke remote setelah commit — backup ke cloud (GitHub) supaya aman dari kerusakan lokal/komputer rusak.
metadata:
  type: feedback
---

Setelah commit sukses di repo ini, **SELALU push ke remote (`git push origin main`)** tanpa ditanya.

**Why:** User ingin work tersimpan di cloud (GitHub) sebagai backup. Kalau ada masalah hardware (komputer rusak, hard disk failure, dll), file lokal bisa hilang tapi history di remote tetap aman. Karena Git = distributed VCS, push = backup gratis.

**How to apply:**
- Setelah `git commit` sukses, langsung jalankan `git push origin {branch}` di command yang sama atau berikutnya.
- Kalau push gagal (network/auth/permission), STOP dan tanya user — jangan paksa.
- Sebelum kerja: cek `git log origin/{branch}..HEAD` untuk lihat commit yang belum di-push.
- Default branch di repo ini: `main`.
- Untuk safety, sebelum kerja besar, bisa `git pull` dulu untuk sync — tapi jangan pull di tengah kerja (bisa conflict).

**Worst case recovery:**
- Local rusak / hilang: `git clone {remote-url}` → semua commit history masih ada di GitHub.
- Push tertolak karena ada conflict: `git pull --rebase`, resolve, lalu `git push` lagi.
- Mau rollback commit yang sudah di-push: `git revert {sha}` (aman) atau `git reset --hard {sha}` + `git push --force` (hati-hati, tanya user dulu).
