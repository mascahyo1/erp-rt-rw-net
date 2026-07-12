#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fetch all Notion page IDs in our sections, build (notion_id, plane_task_id, title, section) tuples,
and write content for each page based on title pattern.
"""
import urllib.request
import json
import os
import sys

NOTION_TOKEN = os.environ.get("NOTION_TOKEN", "")
PLANE_TOKEN = os.environ.get("PLANE_TOKEN", "")
NOTION_API = "https://api.notion.com/v1"
NOTION_VERSION = "2022-06-28"

# Section Notion page IDs
SECTIONS = {
    "Pendahuluan & Topik Lintas Portal": "39b93150-c90e-8146-876d-c4fd616393f5",
    "Topik Umum": "39b93150-c90e-81c7-91b9-f587c3c6cc0e",
    "Halaman Publik / Landing": "39b93150-c90e-8177-a3e0-cacd06c725bb",
    "Operator SaaS": "39b93150-c90e-8106-a7b3-e4ba1f75c277",
    "Admin Perusahaan": "39b93150-c90e-8197-ba6d-d64400947502",
    "Karyawan": "39b93150-c90e-816c-a59b-c4137fad144e",
    "Pelanggan": "39b93150-c90e-8184-9921-c0c0d257078b",
    "Developer Guide": "39b93150-c90e-81d2-b93a-d765ba7f0fdd",
}


def http_get(url, headers):
    req = urllib.request.Request(url, headers=headers, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read().decode()), None
    except Exception as e:
        return None, str(e)


def get_children(block_id, headers):
    """Get all child pages of a block (page or section)."""
    pages = []
    cursor = None
    while True:
        url = f"{NOTION_API}/blocks/{block_id}/children?page_size=100"
        if cursor:
            url += f"&start_cursor={cursor}"
        result, err = http_get(url, headers)
        if err:
            return pages, err
        for b in result.get("results", []):
            if b.get("type") == "child_page":
                pages.append({
                    "notion_id": b["id"],
                    "title": b["child_page"]["title"],
                })
        if not result.get("has_more"):
            break
        cursor = result.get("next_cursor")
    return pages, None


def main():
    headers = {
        "Authorization": f"Bearer {NOTION_TOKEN}",
        "Notion-Version": NOTION_VERSION,
    }
    all_pages = []
    for section_name, section_id in SECTIONS.items():
        pages, err = get_children(section_id, headers)
        if err:
            print(f"ERROR for {section_name}: {err}", file=sys.stderr)
            continue
        print(f"\n## {section_name} ({len(pages)} pages)")
        for p in pages:
            print(f"  {p['notion_id']}\t{p['title']}")
            all_pages.append({
                "section": section_name,
                "notion_id": p["notion_id"],
                "title": p["title"],
            })
    with open("c:\\laragon\\www\\erp-rt-rw-net\\.tmp\\notion_pages.json", "w", encoding="utf-8") as f:
        json.dump(all_pages, f, ensure_ascii=False, indent=2)
    print(f"\n=== Total: {len(all_pages)} pages ===")


if __name__ == "__main__":
    main()