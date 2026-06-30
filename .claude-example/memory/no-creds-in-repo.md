---
name: no-creds-in-repo
description: "JANGAN taruh SSH wrapper scripts, askpass file, atau credential apapun di dalam git repo. Taruh di ~/scripts/ atau ~/.ssh/. Tambah .gitignore defensive pattern."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# NO Credentials / SSH Wrappers di Dalam Repo

## Context
2026-06-30 deploy. Saya bikin `ssh_run.sh`, `ssh_sudo.sh` di `c:\laragon\www\erp-rt-rw-net\` (project root = git repo). User langsung tegur:

> "jangan sampai password ke track git ssh itu contohnya."

Walaupun password literal gak ada di file wrapper (cuma reference ke `~/.ssh/askpass_*.sh`), struktur path/hostname tetap leak + risiko kalau wrapper diedit dan hardcode password, otomatis commit ke repo.

## Why
- Git repo = public-ish (kalau push ke remote)
- Wrapper script biasanya referensi path internal (`/c/Users/masca/...`), hostname (`ssh-smago-ubuntu.cahyosoft.my.id`), user (`cahyo`)
- Askpass file di `~/.ssh/` punya password literal di `echo '<password>'` — itu OK karena di luar repo, TAPI kalau wrapper scripts di repo di-edit & ditambah `echo 'pwd'` = disaster
- `.gitignore` defensive patterns untuk backup safety

## How to apply

### WAJIB:
1. **Wrapper scripts, debug scripts, ssh helpers → taruh di `~/scripts/` atau `~/.ssh/`** (di LUAR repo)
2. **Askpass / credentials file → cuma di `~/.ssh/` atau `~/.config/`** (di luar repo)
3. **Kalau terlanjur ke-create di repo** → `rm` + `.gitignore` pattern
4. **Sebelum `git add`**: `git status` cek, kalau ada file baru aneh (ssh_*, tmp_*, askpass_*) → JANGAN add, move first

### Pattern `.gitignore` defensive:
```gitignore
# Deploy/server scripts (live OUTSIDE repo)
/ssh_run.sh
/ssh_sudo.sh
/ssh_*.sh
/tmp_*.sh
/tmp_*.b64
/tmp_*.py
```

### Workflow yang benar:
1. User minta "ssh ke server"
2. Cek dulu ada `~/scripts/ssh_run.sh`? → pakai itu
3. Kalau belum ada → create di `~/scripts/`, BUKAN di repo
4. Kalau ada di repo (sisa session sebelumnya) → MOVE ke `~/scripts/` atau `rm`

### Cek cepat:
```bash
# Sebelum commit
git status --short | grep -E "ssh_|tmp_|askpass_|passwd|credential"
# → kalau ada, JANGAN commit, move/delete
```

## Related
- [test-artifacts-no-commit](test-artifacts-no-commit.md) — pattern serupa untuk Playwright artifacts
- [deploy-password-policy](deploy-password-policy.md) — password policy