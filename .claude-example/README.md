# `.claude-example/` — Template Memory & Settings untuk Claude Code

Folder ini berisi **template/reference** untuk Claude Code, yang **aman di-commit ke git** dan **terpisah dari `.claude/` user**.

## Bedanya dengan `.claude/`

| Path | Tujuan | Status di git |
|---|---|---|
| `.claude/` | Config user (settings, skills custom, memory yang auto-load) | **Sebagian gitignored** (`settings.json` di `.gitignore`) |
| `.claude-example/` | Template untuk di-copy ke `.claude/` user di komputer lain | **Fully git-tracked** |

## Isi folder

```
.claude-example/
├── README.md                         (file ini)
└── memory/                           (semua memory file template)
    ├── MEMORY.md                     (index/list, di-load pertama setiap sesi)
    ├── *.md                          (1 file = 1 topik memory)
    └── ...
```

## Cara pakai

### Di komputer Anda (sekarang)
1. Folder `.claude-example/` **sudah ada** di project root dan ter-commit
2. Claude Code sudah load memory dari `.claude/projects/.../memory/` (yang juga git-tracked) — **gak perlu copy**

### Di komputer lain (clone repo)
1. Clone repo: `git clone <url>`
2. **Opsional**: copy memory template ke `.claude/projects/<project-path>/memory/`:
   ```bash
   # Linux/Mac
   mkdir -p ~/.claude/projects/$(echo "$PWD" | tr '/:' '__')/memory
   cp .claude-example/memory/*.md ~/.claude/projects/$(echo "$PWD" | tr '/:' '__')/memory/
   
   # Windows PowerShell
   $projectPath = (Get-Location).Path -replace '\\','/' -replace ':','_'
   New-Item -ItemType Directory -Force "$env:USERPROFILE\.claude\projects\$projectPath\memory"
   Copy-Item .claude-example\memory\* "$env:USERPROFILE\.claude\projects\$projectPath\memory\"
   ```
3. Claude Code akan auto-load `MEMORY.md` setiap sesi

## Cara menambah memory baru

1. Cek dulu apakah sudah ada memory yang mirip (baca `MEMORY.md` index)
2. Kalau belum ada → buat file baru di `.claude-example/memory/{slug}.md` dengan format:
   ```markdown
   ---
   name: {slug-kebab-case}
   description: {one-line summary}
   metadata:
     type: user | feedback | project | reference
   ---
   
   {Isi memory — link related dengan [[other-slug]]}
   ```
3. **WAJIB cek dulu tidak ada credential/secrets** sebelum commit:
   ```bash
   grep -iE 'password|secret|api_key|apikey|admin@|@demo\.test|MIDTRANS.*KEY' .claude-example/memory/
   ```
4. Update `MEMORY.md` (tambah 1 baris entry baru)
5. **Copy juga ke `.claude/projects/.../memory/`** di komputer lokal (kalau mau langsung auto-load)
6. Commit + push

## Kapan memory ditulis

Per workflow [teliti-workflow](memory/teliti-workflow.md):
- Setiap ada **feedback/guidance dari user** yang reusable
- Setiap ada **project-specific pattern** yang sering dilupakan
- Setiap ada **gotcha** yang sudah pernah di-debug (supaya gak diulang)

## ⚠️ KEAMANAN: JANGAN masukkan credentials!

Memory files **WAJIB BERSIH** dari:
- ❌ Password (`.env`, akun user, dll)
- ❌ API key (Midtrans, payment gateway, dll)
- ❌ Email user asli
- ❌ URL production
- ❌ Token / secret

Yang boleh:
- ✅ Email test/demo (`test+1781247641870@example.com`, `admin@demo.test`)
- ✅ Password test fixture default (`password123`, `password`)
- ✅ Localhost URL (`erp-rt-rw-net.test`, `127.0.0.1`)
- ✅ Sandbox credentials yang documented public (Midtrans sandbox key)

**Sebelum commit, SELALU audit** dengan grep pattern di atas.
