#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Create 54 Notion sub-pages + update corresponding Plane tasks.
Uses Notion REST API + Plane REST API (both bypass MCP flakiness).
"""
import sys
import io
# Force UTF-8 stdout for Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
import urllib.request
import urllib.error
import json
import os
import sys
import time

# Config
NOTION_TOKEN = os.environ.get("NOTION_TOKEN", "")
PLANE_TOKEN = os.environ.get("PLANE_TOKEN", "")
NOTION_API = "https://api.notion.com/v1"
PLANE_API = "https://api.plane.so/api/v1"
PLANE_WORKSPACE = os.environ.get("PLANE_WORKSPACE", "cahyosoft")
PLANE_PROJECT_UUID = os.environ.get("PLANE_PROJECT_UUID", "35106085-bd7e-4f4b-a057-a7c5e13729fe")
NOTION_VERSION = "2022-06-28"
IN_PROGRESS_STATE_UUID = "935c73ac-fd22-4288-8f18-ee46e92a973b"
PENDIDIKAN_PARENT = "39b93150-c90e-8146-876d-c4fd616393f5"  # Pendahuluan & Topik Lintas Portal
TOPIK_UMUM_PARENT = "39b93150-c90e-81c7-91b9-f587c3c6cc0e"
HIDDEN_PUBLIK_PARENT = "39b93150-c90e-8177-a3e0-cacd06c725bb"  # Halaman Publik / Landing
OP_SAAS_PARENT = "39b93150-c90e-8106-a7b3-e4ba1f75c277"
ADMIN_PERUSAHAAN_PARENT = "39b93150-c90e-8197-ba6d-d64400947502"
KARYAWAN_PARENT = "39b93150-c90e-816c-a59b-c4137fad144e"
PELANGGAN_PARENT = "39b93150-c90e-8184-9921-c0c0d257078b"
DEV_GUIDE_PARENT = "39b93150-c90e-81d2-b93a-d765ba7f0fdd"

# List of (parent_id, title, plane_task_uuid)
PAGES = [
    # Landing (9) - ERPRT-53..61
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] beranda", "6216dc4e-67bb-463f-aab8-b3b89f7c1385"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] tentang-kami", "6ade348a-edde-4e2f-b443-bf0ce4b8f641"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] hubungi-kami", "407fcae5-d379-4915-aee2-df4bf50d5df9"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] kebijakan-privasi", "38ec0c01-4263-443a-9232-a59f5e2936e1"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] syarat-dan-ketentuan", "eded4c21-382c-428d-bf80-631814e0679a"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] login-operator-saas", "2db03fb5-f5dd-4153-8c21-4c37681ef1d7"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] login-perusahaan", "18cf2925-75dd-4e40-9ca3-d68b4922b6cd"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] login-karyawan", "37630240-2387-4995-807d-a7bdfe48d4c9"),
    (HIDDEN_PUBLIK_PARENT, "[UG-Landing] login-pelanggan", "9043da5b-3b5c-42ad-85c2-82c0cce1d73e"),
    # Operator SaaS (10) - ERPRT-26..35
    (OP_SAAS_PARENT, "[UG-OpSaas] dashboard", "82474de0-e5c0-4ff1-817a-2ea43454e77f"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-perusahaan", "a61c2b23-2ee7-49bd-b570-3105953c69c3"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-admin-perusahaan", "0e841a56-3e07-42c8-9a24-6af7794b5a78"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-admin-saas", "79c5885d-56d5-4ffd-b6ff-cade96b0efc9"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-konfigurasi", "7f689d6c-720d-4f90-b115-09f83fa4fb0d"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-role-saas", "5b4f3180-560f-41ce-80d9-c675285d5410"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-admin-role-saas", "7d754817-0a44-4537-9199-4ad6aabb975c"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-role-admin-perusahaan", "9b7d9535-627c-415c-8607-f991a4109faa"),
    (OP_SAAS_PARENT, "[UG-OpSaas] halaman-role-perusahaan", "7db8b276-c1c8-415d-b17a-000db339fad6"),
    (OP_SAAS_PARENT, "[UG-OpSaas] profil-saya", "d2db2f61-fc2d-4514-9363-ff327ef8835f"),
    # Admin Perusahaan (17) - ERPRT-9..25
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] dashboard", "60b57146-84a6-4413-8eb2-770e9a9d3c9c"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] daftar-paket", "64d54f58-ca40-4e1d-a726-5e8a3d555c18"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-langganan", "c469d066-8d05-42f2-ab5d-e7836225b94d"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-karyawan", "107336db-8f08-4314-b57c-1afb4754439c"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-customer", "46d2d7b9-74d3-412a-b5cc-1e98a99466c0"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-admin-perusahaan", "0e1ed0d1-e7e6-4f3e-a055-6b7892550f40"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-tagihan", "745fd395-a19b-47b1-b87c-50349f853625"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-riwayat-pembayaran", "cd456df2-7c90-4ff1-81c6-5971ed9f3f9d"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-insentif", "82672a5b-a810-4d15-b5d4-21e2db8a020b"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-riwayat-insentif", "af31c374-7ba4-4399-95c4-de242fd79785"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-role-perusahaan", "8c1d5be6-61d5-4c73-91ce-2f0ea5304a90"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-admin-role-perusahaan", "7a9ed1bc-11d8-4324-8583-ee9418284cd6"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-admin-role-web-karyawan", "8f621b4d-61e0-453b-9e45-1566affd6fad"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-role-web-karyawan", "0c1e0818-67d2-4b1c-bc69-a6f5586e491b"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] halaman-konfigurasi-perusahaan", "c326fd83-f643-4b57-a5bf-815c5548318a"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] perusahaan-saya", "ad6f07b4-f9fb-4796-bc3d-bcccc8ee2686"),
    (ADMIN_PERUSAHAAN_PARENT, "[UG-OpPerush] profil-saya", "b1432bd4-9e9d-41c1-bb25-3a08defb635f"),
    # Karyawan (7) - ERPRT-46..52
    (KARYAWAN_PARENT, "[UG-Karyawan] dashboard", "5c5a295a-de2c-4d42-84cf-4d6f41f0f0f0"),
    (KARYAWAN_PARENT, "[UG-Karyawan] halaman-customer", "f047bdb4-5651-4417-84a9-0adb661885ed"),
    (KARYAWAN_PARENT, "[UG-Karyawan] halaman-langganan", "721ba554-f107-4cb7-a86a-dce94131b313"),
    (KARYAWAN_PARENT, "[UG-Karyawan] halaman-tagihan", "151f5507-c854-43bb-9054-115ec447cdcd"),
    (KARYAWAN_PARENT, "[UG-Karyawan] halaman-riwayat-pembayaran", "5bc4372f-9e4b-4408-b0f4-3c5bb6f336ff"),
    (KARYAWAN_PARENT, "[UG-Karyawan] halaman-insentif", "4f708c57-ff9e-4fb4-9a31-5f477c39d1bd"),
    (KARYAWAN_PARENT, "[UG-Karyawan] profil-saya", "ac6849b4-9b77-4f4a-adfa-0bd4aa50c0e5"),
    # Pelanggan (10) - ERPRT-36..45
    (PELANGGAN_PARENT, "[UG-Pelanggan] dashboard", "397d7398-15d8-43f2-8775-8724ebba49f3"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] paket-saya", "143c94f8-1458-4571-938c-d72fc349fbea"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] paket-tambah", "b2dd6a6b-e7d5-453b-ac5b-25fdeedd6a45"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] paket-detail", "cc96987d-0521-473b-894b-7797acd48f76"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] tagihan-saya", "ed7cd054-1fd7-4383-ae29-00698fd745a9"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] tagihan-detail", "2cbea49e-886c-4680-b201-375e3c89d0af"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] pembayaran-tambah", "7aaff052-8725-43a3-9439-65e345f1451d"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] pembayaran-detail", "9cc2a391-d795-4be6-b75c-0508f60d67e4"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] riwayat-pembayaran", "7ee4c842-fcd2-491d-b27f-ff31355705c3"),
    (PELANGGAN_PARENT, "[UG-Pelanggan] profil-saya", "ca26230b-1d4e-4dcd-afcf-9935cb783d3e"),
    # Developer Guide (1) - ERPRT-8
    (DEV_GUIDE_PARENT, "[DG-Master] Developer Guide (Master)", "5ea74804-1258-40d4-b877-d30dd5e13cfb"),
]


def http_post(url, headers, data):
    req = urllib.request.Request(url, data=json.dumps(data).encode(), headers=headers, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read().decode()), None
    except urllib.error.HTTPError as e:
        return None, f"HTTP {e.code}: {e.read().decode()[:300]}"
    except Exception as e:
        return None, str(e)


def http_patch(url, headers, data):
    req = urllib.request.Request(url, data=json.dumps(data).encode(), headers=headers, method="PATCH")
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read().decode()), None
    except urllib.error.HTTPError as e:
        return None, f"HTTP {e.code}: {e.read().decode()[:300]}"
    except Exception as e:
        return None, str(e)


def create_notion_page(parent_id, title):
    headers = {
        "Authorization": f"Bearer {NOTION_TOKEN}",
        "Content-Type": "application/json",
        "Notion-Version": NOTION_VERSION,
    }
    data = {
        "parent": {"page_id": parent_id},
        "properties": {
            "title": [{"text": {"content": title}}]
        },
    }
    return http_post(f"{NOTION_API}/pages", headers, data)


def update_plane_state(plane_task_uuid):
    headers = {
        "x-api-key": PLANE_TOKEN,
        "Content-Type": "application/json",
    }
    data = {"state": IN_PROGRESS_STATE_UUID}
    return http_patch(
        f"{PLANE_API}/workspaces/{PLANE_WORKSPACE}/projects/{PLANE_PROJECT_UUID}/work-items/{plane_task_uuid}/",
        headers,
        data,
    )


def main():
    print(f"Total pages to create: {len(PAGES)}")
    print("=" * 80)
    success = 0
    failed = []
    for i, (parent_id, title, plane_id) in enumerate(PAGES, 1):
        page_result, err = create_notion_page(parent_id, title)
        if err:
            print(f"[{i:2d}/{len(PAGES)}] ❌ FAIL: {title} — {err}")
            failed.append((title, plane_id, "notion"))
            continue
        notion_id = page_result.get("id", "?")
        print(f"[{i:2d}/{len(PAGES)}] ✅ Notion: {title} → {notion_id}")
        # Update Plane state
        plane_result, err2 = update_plane_state(plane_id)
        if err2:
            print(f"          ⚠️  Plane update failed for {plane_id}: {err2}")
            failed.append((title, plane_id, "plane"))
            continue
        success += 1
        # Rate limit courtesy
        time.sleep(0.3)
    print("=" * 80)
    print(f"\n✅ Success: {success}/{len(PAGES)}")
    if failed:
        print(f"❌ Failed: {len(failed)}")
        for title, plane_id, stage in failed:
            print(f"  - [{stage}] {title} (plane: {plane_id})")


if __name__ == "__main__":
    main()
