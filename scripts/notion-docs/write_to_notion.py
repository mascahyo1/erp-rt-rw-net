#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Write content to Notion via REST API:
1. Parse markdown to Notion blocks
2. Delete existing children
3. POST new blocks
4. Update Plane → Done
"""
import urllib.request
import json
import os
import re
import sys
import io

# Force UTF-8 stdout for Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8")
import time

# Open log file for line-buffered output
LOG_FILE = "c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\write_log.txt"
log_fh = open(LOG_FILE, "w", encoding="utf-8", buffering=1)  # line-buffered


def log(msg):
    log_fh.write(msg + "\n")
    log_fh.flush()
    sys.stdout.write(msg + "\n")
    sys.stdout.flush()

NOTION_TOKEN = os.environ.get("NOTION_TOKEN", "")
PLANE_TOKEN = os.environ.get("PLANE_TOKEN", "")
NOTION_API = "https://api.notion.com/v1"
PLANE_API = "https://api.plane.so/api/v1"
PLANE_WORKSPACE = os.environ.get("PLANE_WORKSPACE", "cahyosoft")
PLANE_PROJECT_UUID = os.environ.get("PLANE_PROJECT_UUID", "35106085-bd7e-4f4b-a057-a7c5e13729fe")
NOTION_VERSION = "2022-06-28"
DONE_STATE_UUID = "74581ccf-2ad8-4e4c-8893-4e74fe58bd89"


def http_request(url, headers, method="GET", data=None):
    body = json.dumps(data).encode() if data else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read().decode()), None
    except urllib.error.HTTPError as e:
        return None, f"HTTP {e.code}: {e.read().decode()[:500]}"
    except Exception as e:
        return None, str(e)


def parse_inline(text):
    """Parse inline markdown: **bold**, *italic*, `code`, [text](url). Returns rich_text array."""
    rich_text = []
    pattern = r"(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`|\[[^\]]+\]\([^)]+\))"
    parts = re.split(pattern, text)
    for part in parts:
        if not part:
            continue
        annotations = {"bold": False, "italic": False, "strikethrough": False, "underline": False, "code": False, "color": "default"}
        link_url = None
        if part.startswith("**") and part.endswith("**"):
            annotations["bold"] = True
            content = part[2:-2]
        elif part.startswith("*") and part.endswith("*") and len(part) > 2:
            annotations["italic"] = True
            content = part[1:-1]
        elif part.startswith("`") and part.endswith("`"):
            annotations["code"] = True
            content = part[1:-1]
        elif part.startswith("[") and "](" in part and part.endswith(")"):
            link_match = re.match(r"\[([^\]]+)\]\(([^)]+)\)", part)
            if link_match:
                content = link_match.group(1)
                link_url = link_match.group(2)
                # Skip invalid URL schemes (placeholder #, relative paths without http)
                if link_url and not (link_url.startswith("http://") or link_url.startswith("https://")):
                    link_url = None  # render as plain text
            else:
                content = part
        else:
            content = part
        rt = {"type": "text", "text": {"content": content}}
        if link_url:
            rt["text"]["link"] = {"url": link_url}
        if any(annotations.values()):
            rt["annotations"] = annotations
        rich_text.append(rt)
    if not rich_text:
        rich_text.append({"type": "text", "text": {"content": ""}})
    return rich_text


def md_to_blocks(md_text):
    """Convert markdown to Notion blocks. Returns list of block objects."""
    blocks = []
    lines = md_text.split("\n")
    i = 0
    while i < len(lines):
        line = lines[i].rstrip()
        if not line.strip():
            i += 1
            continue
        # Headings
        if line.startswith("### "):
            blocks.append({
                "object": "block",
                "type": "heading_3",
                "heading_3": {"rich_text": parse_inline(line[4:])}
            })
        elif line.startswith("## "):
            blocks.append({
                "object": "block",
                "type": "heading_2",
                "heading_2": {"rich_text": parse_inline(line[3:])}
            })
        elif line.startswith("# "):
            blocks.append({
                "object": "block",
                "type": "heading_1",
                "heading_1": {"rich_text": parse_inline(line[2:])}
            })
        # Quote (multi-line)
        elif line.startswith("> "):
            quote_lines = []
            while i < len(lines) and lines[i].startswith("> "):
                quote_lines.append(lines[i][2:])
                i += 1
            blocks.append({
                "object": "block",
                "type": "quote",
                "quote": {"rich_text": parse_inline(" ".join(quote_lines))}
            })
            continue
        # Horizontal rule
        elif line.strip() == "---":
            blocks.append({"object": "block", "type": "divider", "divider": {}})
        # Code block (multi-line)
        elif line.startswith("```"):
            lang = line[3:].strip()
            code_lines = []
            i += 1
            while i < len(lines) and not lines[i].startswith("```"):
                code_lines.append(lines[i])
                i += 1
            i += 1  # skip closing ```
            blocks.append({
                "object": "block",
                "type": "code",
                "code": {
                    "rich_text": [{"type": "text", "text": {"content": "\n".join(code_lines)}}],
                    "language": lang if lang else "plain text"
                }
            })
            continue
        # Bulleted list (collect consecutive)
        elif line.startswith("- ") or line.startswith("* "):
            list_items = []
            while i < len(lines) and (lines[i].startswith("- ") or lines[i].startswith("* ")):
                item_text = lines[i][2:]
                list_items.append(item_text)
                i += 1
            for item in list_items:
                blocks.append({
                    "object": "block",
                    "type": "bulleted_list_item",
                    "bulleted_list_item": {"rich_text": parse_inline(item)}
                })
            continue
        # Numbered list (collect consecutive)
        elif re.match(r"^\d+\.\s", line):
            list_items = []
            while i < len(lines) and re.match(r"^\d+\.\s", lines[i]):
                item_text = re.sub(r"^\d+\.\s", "", lines[i])
                list_items.append(item_text)
                i += 1
            for item in list_items:
                blocks.append({
                    "object": "block",
                    "type": "numbered_list_item",
                    "numbered_list_item": {"rich_text": parse_inline(item)}
                })
            continue
        # Table (simple detection - convert rows to paragraphs for now)
        elif line.startswith("|"):
            # Skip tables for now (complex parsing)
            table_lines = []
            while i < len(lines) and lines[i].startswith("|"):
                table_lines.append(lines[i])
                i += 1
            # Add as a single code block (preserves formatting better)
            blocks.append({
                "object": "block",
                "type": "code",
                "code": {
                    "rich_text": [{"type": "text", "text": {"content": "\n".join(table_lines)}}],
                    "language": "markdown"
                }
            })
            continue
        # Sub text (italic note)
        elif line.startswith("<sub>"):
            content = re.search(r"<sub>(.*?)</sub>", line)
            if content:
                blocks.append({
                    "object": "block",
                    "type": "paragraph",
                    "paragraph": {"rich_text": [
                        {"type": "text", "text": {"content": content.group(1)},
                         "annotations": {"italic": True, "color": "gray"}}
                    ]}
                })
        # Default: paragraph
        else:
            blocks.append({
                "object": "block",
                "type": "paragraph",
                "paragraph": {"rich_text": parse_inline(line)}
            })
        i += 1
    return blocks


def get_children(block_id, headers):
    """Get all child blocks."""
    children = []
    cursor = None
    while True:
        url = f"{NOTION_API}/blocks/{block_id}/children?page_size=100"
        if cursor:
            url += f"&start_cursor={cursor}"
        result, err = http_request(url, headers)
        if err:
            return children, err
        children.extend(result.get("results", []))
        if not result.get("has_more"):
            break
        cursor = result.get("next_cursor")
    return children, None


def delete_block(block_id, headers):
    """Archive (delete) a block."""
    _, err = http_request(f"{NOTION_API}/blocks/{block_id}", headers, method="DELETE")
    return err


def append_blocks(block_id, blocks, headers):
    """Append blocks to a page in chunks of 100."""
    for i in range(0, len(blocks), 100):
        chunk = blocks[i:i+100]
        _, err = http_request(
            f"{NOTION_API}/blocks/{block_id}/children",
            headers,
            method="PATCH",
            data={"children": chunk}
        )
        if err:
            return err
    return None


def update_plane_state(plane_task_uuid, headers):
    """Update Plane task state. Try both endpoint variations."""
    # Try /issues/ first (Plane standard endpoint)
    endpoints = [
        f"{PLANE_API}/workspaces/{PLANE_WORKSPACE}/projects/{PLANE_PROJECT_UUID}/issues/{plane_task_uuid}/",
        f"{PLANE_API}/workspaces/{PLANE_WORKSPACE}/projects/{PLANE_PROJECT_UUID}/work-items/{plane_task_uuid}/",
    ]
    data = {"state": DONE_STATE_UUID}
    last_err = None
    for url in endpoints:
        _, err = http_request(url, headers, method="PATCH", data=data)
        if not err:
            return None
        last_err = err
    return last_err


def process_page(page, notion_headers, plane_headers):
    """Process a single page: write content + optionally update Plane."""
    notion_id = page["notion_id"]
    plane_id = page["plane_id"]
    title = page["title"]
    content = page["content"]

    if not plane_id:
        return None, "no plane_id"

    # Parse markdown to blocks
    blocks = md_to_blocks(content)
    if not blocks:
        return None, "no blocks generated"

    # Get existing children
    existing, err = get_children(notion_id, notion_headers)
    if err:
        return None, f"get_children error: {err}"

    # Delete existing children (in batches)
    for child in existing:
        if child.get("type") == "child_page":
            continue  # skip child pages
        delete_block(child["id"], notion_headers)

    # Append new blocks (chunked to 100)
    err = append_blocks(notion_id, blocks, notion_headers)
    if err:
        return None, f"append error: {err}"

    # Try update Plane state (may fail - we'll collect separately)
    plane_result = update_plane_state(plane_id, plane_headers)
    return plane_result, None  # tuple: (plane_result_or_None, content_error_or_None)


def main():
    # Load pages with content
    with open("c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\pages_with_content.json", "r", encoding="utf-8") as f:
        pages = json.load(f)

    # Skip already-done
    done_already = {"6051034d-c4ed-4112-839f-5179a7e5ee63", "745fd395-a19b-47b1-b87c-50349f853625"}
    pages = [p for p in pages if p["plane_id"] not in done_already]

    print(f"Total to process: {len(pages)}")
    print()
    log(f"Total to process: {len(pages)}")

    notion_headers = {
        "Authorization": f"Bearer {NOTION_TOKEN}",
        "Notion-Version": NOTION_VERSION,
        "Content-Type": "application/json",
    }
    plane_headers = {
        "x-api-key": PLANE_TOKEN,
        "Content-Type": "application/json",
    }

    success = 0
    failed_content = []
    failed_plane = []
    for i, page in enumerate(pages, 1):
        plane_result, content_err = process_page(page, notion_headers, plane_headers)
        if content_err:
            failed_content.append((page['title'], page['plane_id'], content_err))
            log(f"[{i:2d}/{len(pages)}] X {page['title']}: {content_err}")
        else:
            success += 1
            plane_status = "planeOK" if plane_result is None else "planeRESTfail"
            log(f"[{i:2d}/{len(pages)}] notionOK/{plane_status} {page['title']}")
            if plane_result is not None:
                failed_plane.append((page['plane_id'], page['title']))
        time.sleep(0.3)

    log("")
    log("=" * 70)
    log(f"Notion success: {success}/{len(pages)}")
    log(f"Plane REST failed: {len(failed_plane)} (need MCP backup)")
    if failed_plane:
        with open("c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\plane_pending.json", "w", encoding="utf-8") as f:
            json.dump(failed_plane, f, ensure_ascii=False, indent=2)
        log(f"Saved to .tmp/plane_pending.json")
    if failed_content:
        log(f"Notion content errors: {len(failed_content)}")
        for title, plane_id, err in failed_content:
            log(f"  - {title} (plane: {plane_id}): {err[:100]}")


if __name__ == "__main__":
    main()