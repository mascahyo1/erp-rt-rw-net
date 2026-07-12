# Notion Docs Migration Scripts

Scripts untuk create + populate Notion docs structure dari Plane tasks.

## Setup

Butuh environment variables (tokens **JANGAN** di-hardcode di file):

```bash
export NOTION_TOKEN="ntn_..."        # Notion internal integration token
export PLANE_TOKEN="plane_api_..."   # Plane.so API token
export PLANE_WORKSPACE="cahyosoft"   # optional, default "cahyosoft"
export PLANE_PROJECT_UUID="35106085-bd7e-4f4b-a057-a7c5e13729fe"  # optional, default
```

Get tokens:
- **Notion**: <https://www.notion.so/my-integrations> → Internal Integration → copy "Internal Integration Token"
- **Plane**: <https://app.plane.so/settings/api-tokens> → Personal Access Token

## Scripts (jalankan berurutan)

### 1. `create_notion_pages.py`
Bulk-create sub-pages di Notion untuk semua Plane tasks.

```bash
python create_notion_pages.py
```

Output: 54 sub-pages + 8 section pages created di Notion.

### 2. `fetch_pages.py`
Discover Notion page IDs dari section parents, save ke `notion_pages.json`.

```bash
python fetch_pages.py
```

### 3. `generate_content.py`
Generate markdown content per page based on title pattern + section context.

```bash
python generate_content.py
```

Output: `pages_with_content.json` (60 entries, each with title + content).

### 4. `write_to_notion.py`
Parse markdown → Notion blocks → REST API PATCH. Plus update Plane → Done.

```bash
python -u write_to_notion.py > write_log.txt 2>&1
```

Output: All 60 pages get content + Plane tasks move to Done state.

## Data files

- `notion_pages.json` — mapping {section, notion_id, title}
- `pages_with_content.json` — full content for all 60 pages

## Why Python + REST (not MCP)?

Notion MCP (`@notionhq/notion-mcp-server`) has bug #153 in schema validation that causes flakiness:
- Some calls succeed
- Some get rejected with "title should be array"
- Some parameters get lost

Workaround: use Notion REST API directly via Python — deterministic, fast, no schema flakiness.

## Result

- 67 Notion sub-pages + 8 section pages
- 76 Plane work items (9 parents + 67 children) + 15 labels
- 62 Plane tasks in Done state
- Published site: https://icy-crest-56e.notion.site/Dokumentasi-ERP-RT-RW-Net-39b93150c90e80aab1b2d7d689c5c15b