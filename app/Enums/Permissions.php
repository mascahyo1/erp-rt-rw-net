<?php

namespace App\Enums;

enum Permissions: string
{
    // ============================================================
    // OPERATOR SAAS (8 module CRUD + 1 konfigurasi)
    // ============================================================
    case AdminPerusahaanList   = 'admin-perusahaan.list';
    case AdminPerusahaanCreate = 'admin-perusahaan.create';
    case AdminPerusahaanEdit   = 'admin-perusahaan.edit';
    case AdminPerusahaanDetail = 'admin-perusahaan.detail';
    case AdminPerusahaanDelete = 'admin-perusahaan.delete';
    case AdminPerusahaanRestore = 'admin-perusahaan.restore';

    case PerusahaanList   = 'perusahaan.list';
    case PerusahaanCreate = 'perusahaan.create';
    case PerusahaanEdit   = 'perusahaan.edit';
    case PerusahaanDetail = 'perusahaan.detail';
    case PerusahaanDelete = 'perusahaan.delete';
    case PerusahaanRestore = 'perusahaan.restore';

    case RolePerusahaanList   = 'role-perusahaan.list';
    case RolePerusahaanCreate = 'role-perusahaan.create';
    case RolePerusahaanEdit   = 'role-perusahaan.edit';
    case RolePerusahaanDelete = 'role-perusahaan.delete';
    case RolePerusahaanRestore = 'role-perusahaan.restore';

    case RoleAdminPerusahaanList   = 'role-admin-perusahaan.list';
    case RoleAdminPerusahaanCreate = 'role-admin-perusahaan.create';
    case RoleAdminPerusahaanEdit   = 'role-admin-perusahaan.edit';
    case RoleAdminPerusahaanDelete = 'role-admin-perusahaan.delete';

    case KonfigurasiList   = 'konfigurasi.list';
    case KonfigurasiCreate = 'konfigurasi.create';
    case KonfigurasiEdit   = 'konfigurasi.edit';
    case KonfigurasiDelete = 'konfigurasi.delete';
    case KonfigurasiRestore = 'konfigurasi.restore';
    case KonfigurasiExport = 'konfigurasi.export';
    case KonfigurasiImport = 'konfigurasi.import';

    case RoleSaaSList   = 'role-saas.list';
    case RoleSaaSCreate = 'role-saas.create';
    case RoleSaaSEdit   = 'role-saas.edit';
    case RoleSaaSDelete = 'role-saas.delete';
    case RoleSaaSRestore = 'role-saas.restore';

    case AdminSaaSList   = 'admin-saas.list';
    case AdminSaaSCreate = 'admin-saas.create';
    case AdminSaaSEdit   = 'admin-saas.edit';
    case AdminSaaSDetail = 'admin-saas.detail';
    case AdminSaaSDelete = 'admin-saas.delete';
    case AdminSaaSRestore = 'admin-saas.restore';

    case AdminRoleSaaSList   = 'admin-role-saas.list';
    case AdminRoleSaaSCreate = 'admin-role-saas.create';
    case AdminRoleSaaSEdit   = 'admin-role-saas.edit';
    case AdminRoleSaaSDelete = 'admin-role-saas.delete';

    // ============================================================
    // ADMIN PERUSAHAAN (16 module)
    // ============================================================
    case PerusahaanSayaList = 'perusahaan-saya.list';
    case PerusahaanSayaDetail = 'perusahaan-saya.detail';
    case PerusahaanSayaEdit = 'perusahaan-saya.edit';
    case PaketList = 'paket.list';
    case PaketCreate = 'paket.create';
    case PaketEdit = 'paket.edit';
    case PaketDetail = 'paket.detail';
    case PaketDelete = 'paket.delete';
    case PaketRestore = 'paket.restore';
    case PaketExport   = 'paket.export';
    case PaketImport   = 'paket.import';

    // Customer (full CRUD)
    case CustomerList   = 'customer.list';
    case CustomerCreate = 'customer.create';
    case CustomerEdit   = 'customer.edit';
    case CustomerDetail = 'customer.detail';
    case CustomerDelete = 'customer.delete';
    case CustomerRestore = 'customer.restore';
    case CustomerExport  = 'customer.export';
    case CustomerImport  = 'customer.import';
    // Manual override email_verified_at (safety net kalau customer terkendala email)
    case CustomerVerifyEmail = 'customer.verify-email';

    // Langganan (full CRUD)
    case LanggananList   = 'langganan.list';
    case LanggananCreate = 'langganan.create';
    case LanggananEdit   = 'langganan.edit';
    case LanggananDetail = 'langganan.detail';
    case LanggananDelete = 'langganan.delete';
    case LanggananRestore = 'langganan.restore';
    case LanggananExport  = 'langganan.export';
    case LanggananImport  = 'langganan.import';

    // Tagihan (full CRUD)
    case TagihanList   = 'tagihan.list';
    case TagihanCreate = 'tagihan.create';
    case TagihanEdit   = 'tagihan.edit';
    case TagihanDetail = 'tagihan.detail';
    case TagihanDelete = 'tagihan.delete';
    case TagihanRestore = 'tagihan.restore';
    case TagihanExport  = 'tagihan.export';
    case TagihanImport  = 'tagihan.import';
    case TagihanGenerate = 'tagihan.generate';

    // Insentif (full CRUD)
    case InsentifList   = 'insentif.list';
    case InsentifCreate = 'insentif.create';
    case InsentifEdit   = 'insentif.edit';
    case InsentifDetail = 'insentif.detail';
    case InsentifDelete = 'insentif.delete';
    case InsentifRestore = 'insentif.restore';
    case InsentifExport  = 'insentif.export';
    case InsentifImport  = 'insentif.import';

    // Riwayat Insentif (full CRUD + persetujuan)
    case RiwayatInsentifList        = 'riwayat-insentif.list';
    case RiwayatInsentifCreate      = 'riwayat-insentif.create';
    case RiwayatInsentifEdit        = 'riwayat-insentif.edit';
    case RiwayatInsentifDetail      = 'riwayat-insentif.detail';
    case RiwayatInsentifDelete      = 'riwayat-insentif.delete';
    case RiwayatInsentifRestore     = 'riwayat-insentif.restore';
    case RiwayatInsentifPersetujuan = 'riwayat-insentif.persetujuan';
    case RiwayatInsentifExport      = 'riwayat-insentif.export';
    case RiwayatInsentifImport      = 'riwayat-insentif.import';

    // Riwayat Pembayaran (full CRUD + persetujuan + import/export)
    case RiwayatPembayaranList        = 'riwayat-pembayaran.list';
    case RiwayatPembayaranCreate      = 'riwayat-pembayaran.create';
    case RiwayatPembayaranEdit        = 'riwayat-pembayaran.edit';
    case RiwayatPembayaranDetail      = 'riwayat-pembayaran.detail';
    case RiwayatPembayaranDelete      = 'riwayat-pembayaran.delete';
    case RiwayatPembayaranRestore     = 'riwayat-pembayaran.restore';
    case RiwayatPembayaranPersetujuan = 'riwayat-pembayaran.persetujuan';
    case RiwayatPembayaranExport      = 'riwayat-pembayaran.export';
    case RiwayatPembayaranImport      = 'riwayat-pembayaran.import';

    // Gangguan / Support Ticket (admin perusahaan: full CRUD + verify + import/export)
    case GangguanList     = 'gangguan.list';
    case GangguanCreate   = 'gangguan.create';
    case GangguanEdit     = 'gangguan.edit';
    case GangguanDetail   = 'gangguan.detail';
    case GangguanDelete   = 'gangguan.delete';
    case GangguanRestore  = 'gangguan.restore';
    case GangguanVerify   = 'gangguan.verify';
    case GangguanExport   = 'gangguan.export';
    case GangguanImport   = 'gangguan.import';

    // Performa Karyawan (admin perusahaan: read-only report — kinerja + insentif + gangguan solved)
    case PerformaKaryawanList   = 'performa-karyawan.list';
    case PerformaKaryawanExport = 'performa-karyawan.export';

    // Karyawan (full CRUD)
    case KaryawanList   = 'karyawan.list';
    case KaryawanCreate = 'karyawan.create';
    case KaryawanEdit   = 'karyawan.edit';
    case KaryawanDetail = 'karyawan.detail';
    case KaryawanDelete = 'karyawan.delete';
    case KaryawanRestore = 'karyawan.restore';
    case KaryawanExport = 'karyawan.export';
    case KaryawanImport = 'karyawan.import';

    // View-only sub-modules → now full CRUD
    case RolePerusahaanOpList   = 'role-perusahaan-op.list';
    case RolePerusahaanOpCreate = 'role-perusahaan-op.create';
    case RolePerusahaanOpEdit   = 'role-perusahaan-op.edit';
    case RolePerusahaanOpDelete = 'role-perusahaan-op.delete';
    case RolePerusahaanOpRestore = 'role-perusahaan-op.restore';
    case AdminRolePerusahaanOpList   = 'admin-role-perusahaan-op.list';
    case AdminRolePerusahaanOpCreate = 'admin-role-perusahaan-op.create';
    case AdminRolePerusahaanOpEdit   = 'admin-role-perusahaan-op.edit';
    case AdminRolePerusahaanOpDelete = 'admin-role-perusahaan-op.delete';
    case AdminRolePerusahaanOpExport = 'admin-role-perusahaan-op.export';
    case AdminRolePerusahaanOpImport = 'admin-role-perusahaan-op.import';
    case RoleWebKaryawanList   = 'role-web-karyawan.list';
    case RoleWebKaryawanCreate = 'role-web-karyawan.create';
    case RoleWebKaryawanEdit   = 'role-web-karyawan.edit';
    case RoleWebKaryawanDelete = 'role-web-karyawan.delete';
    case RoleWebKaryawanRestore = 'role-web-karyawan.restore';
    case AdminRoleWebKaryawanList   = 'admin-role-web-karyawan.list';
    case AdminRoleWebKaryawanCreate = 'admin-role-web-karyawan.create';
    case AdminRoleWebKaryawanEdit   = 'admin-role-web-karyawan.edit';
    case AdminRoleWebKaryawanDelete = 'admin-role-web-karyawan.delete';
    case AdminRoleWebKaryawanExport = 'admin-role-web-karyawan.export';
    case AdminRoleWebKaryawanImport = 'admin-role-web-karyawan.import';
    case KonfigurasiPerusahaanList   = 'konfigurasi-perusahaan.list';
    case KonfigurasiPerusahaanCreate = 'konfigurasi-perusahaan.create';
    case KonfigurasiPerusahaanEdit   = 'konfigurasi-perusahaan.edit';
    case KonfigurasiPerusahaanDelete = 'konfigurasi-perusahaan.delete';
    case KonfigurasiPerusahaanRestore = 'konfigurasi-perusahaan.restore';
    case KonfigurasiPerusahaanExport = 'konfigurasi-perusahaan.export';
    case KonfigurasiPerusahaanImport = 'konfigurasi-perusahaan.import';

    // ============================================================
    // KARYAWAN (7 module — 1 view-only + 5 full CRUD + profil)
    // ============================================================
    case ProfilSayaList = 'profil-saya.list';

    // Customer (full CRUD via karyawan)
    case KaryawanCustomerList   = 'karyawan-customer.list';
    case KaryawanCustomerCreate = 'karyawan-customer.create';
    case KaryawanCustomerEdit   = 'karyawan-customer.edit';
    case KaryawanCustomerDetail = 'karyawan-customer.detail';
    case KaryawanCustomerDelete = 'karyawan-customer.delete';
    case KaryawanCustomerRestore = 'karyawan-customer.restore';
    case KaryawanCustomerImport = 'karyawan-customer.import';
    case KaryawanCustomerExport = 'karyawan-customer.export';
    // Manual override email_verified_at via web karyawan
    case KaryawanCustomerVerifyEmail = 'karyawan-customer.verify-email';

    // Langganan (full CRUD via karyawan)
    case KaryawanLanggananList   = 'karyawan-langganan.list';
    case KaryawanLanggananCreate = 'karyawan-langganan.create';
    case KaryawanLanggananEdit   = 'karyawan-langganan.edit';
    case KaryawanLanggananDetail = 'karyawan-langganan.detail';
    case KaryawanLanggananDelete = 'karyawan-langganan.delete';
    case KaryawanLanggananRestore = 'karyawan-langganan.restore';
    case KaryawanLanggananImport = 'karyawan-langganan.import';
    case KaryawanLanggananExport = 'karyawan-langganan.export';

    // Tagihan (full CRUD via karyawan)
    case KaryawanTagihanList   = 'karyawan-tagihan.list';
    case KaryawanTagihanCreate = 'karyawan-tagihan.create';
    case KaryawanTagihanEdit   = 'karyawan-tagihan.edit';
    case KaryawanTagihanDetail = 'karyawan-tagihan.detail';
    case KaryawanTagihanDelete = 'karyawan-tagihan.delete';
    case KaryawanTagihanRestore = 'karyawan-tagihan.restore';
    case KaryawanTagihanImport = 'karyawan-tagihan.import';
    case KaryawanTagihanExport = 'karyawan-tagihan.export';
    case KaryawanTagihanGenerate = 'karyawan-tagihan.generate';

    // Insentif (full CRUD via karyawan)
    case KaryawanInsentifList   = 'karyawan-insentif.list';
    case KaryawanInsentifCreate = 'karyawan-insentif.create';
    case KaryawanInsentifEdit   = 'karyawan-insentif.edit';
    case KaryawanInsentifDetail = 'karyawan-insentif.detail';
    case KaryawanInsentifDelete = 'karyawan-insentif.delete';
    case KaryawanInsentifRestore = 'karyawan-insentif.restore';

    // Riwayat Pembayaran (full CRUD via karyawan)
    case KaryawanRiwayatPembayaranList   = 'karyawan-riwayat-pembayaran.list';
    case KaryawanRiwayatPembayaranCreate = 'karyawan-riwayat-pembayaran.create';
    case KaryawanRiwayatPembayaranEdit   = 'karyawan-riwayat-pembayaran.edit';
    case KaryawanRiwayatPembayaranDetail = 'karyawan-riwayat-pembayaran.detail';
    case KaryawanRiwayatPembayaranDelete = 'karyawan-riwayat-pembayaran.delete';
    case KaryawanRiwayatPembayaranRestore = 'karyawan-riwayat-pembayaran.restore';
    case KaryawanRiwayatPembayaranPersetujuan = 'karyawan-riwayat-pembayaran.persetujuan';
    case KaryawanRiwayatPembayaranExport = 'karyawan-riwayat-pembayaran.export';
    case KaryawanRiwayatPembayaranImport = 'karyawan-riwayat-pembayaran.import';

    // Gangguan (karyawan: full CRUD + resolve + import/export — tanpa verify, itu admin perusahaan)
    case KaryawanGangguanList     = 'karyawan-gangguan.list';
    case KaryawanGangguanCreate   = 'karyawan-gangguan.create';
    case KaryawanGangguanEdit     = 'karyawan-gangguan.edit';
    case KaryawanGangguanDetail   = 'karyawan-gangguan.detail';
    case KaryawanGangguanDelete   = 'karyawan-gangguan.delete';
    case KaryawanGangguanRestore  = 'karyawan-gangguan.restore';
    case KaryawanGangguanResolve  = 'karyawan-gangguan.resolve';
    case KaryawanGangguanExport   = 'karyawan-gangguan.export';
    case KaryawanGangguanImport   = 'karyawan-gangguan.import';

    // Paket (read-only via karyawan, no CRUD)
    case KaryawanPaketList   = 'karyawan-paket.list';
    case KaryawanPaketDetail = 'karyawan-paket.detail';

    // ============================================================
    // SCOPE MAPPING
    // ============================================================

    public static function forScope(string $scope): array
    {
        return match ($scope) {
            'operator_saas' => [
                self::AdminPerusahaanList->value,
                self::AdminPerusahaanCreate->value,
                self::AdminPerusahaanEdit->value,
                self::AdminPerusahaanDetail->value,
                self::AdminPerusahaanDelete->value,
                self::AdminPerusahaanRestore->value,
                self::PerusahaanList->value,
                self::PerusahaanCreate->value,
                self::PerusahaanEdit->value,
                self::PerusahaanDetail->value,
                self::PerusahaanDelete->value,
                self::PerusahaanRestore->value,
                self::RolePerusahaanList->value,
                self::RolePerusahaanCreate->value,
                self::RolePerusahaanEdit->value,
                self::RolePerusahaanDelete->value,
                self::RolePerusahaanRestore->value,
                self::RoleAdminPerusahaanList->value,
                self::RoleAdminPerusahaanCreate->value,
                self::RoleAdminPerusahaanEdit->value,
                self::RoleAdminPerusahaanDelete->value,
                self::KonfigurasiList->value,
                self::KonfigurasiCreate->value,
                self::KonfigurasiEdit->value,
                self::KonfigurasiDelete->value,
                self::KonfigurasiRestore->value,
                self::KonfigurasiExport->value,
                self::KonfigurasiImport->value,
                self::RoleSaaSList->value,
                self::RoleSaaSCreate->value,
                self::RoleSaaSEdit->value,
                self::RoleSaaSDelete->value,
                self::RoleSaaSRestore->value,
                self::AdminSaaSList->value,
                self::AdminSaaSCreate->value,
                self::AdminSaaSEdit->value,
                self::AdminSaaSDetail->value,
                self::AdminSaaSDelete->value,
                self::AdminSaaSRestore->value,
                self::AdminRoleSaaSList->value,
                self::AdminRoleSaaSCreate->value,
                self::AdminRoleSaaSEdit->value,
                self::AdminRoleSaaSDelete->value,
            ],
            'admin_perusahaan' => [
                self::PerusahaanSayaDetail->value,
                self::PerusahaanSayaEdit->value,
                self::PaketList->value,
                self::PaketCreate->value,
                self::PaketEdit->value,
                self::PaketDetail->value,
                self::PaketDelete->value,
                self::PaketRestore->value,
                self::PaketExport->value,
                self::PaketImport->value,
                self::AdminPerusahaanList->value,
                self::AdminPerusahaanCreate->value,
                self::AdminPerusahaanEdit->value,
                self::AdminPerusahaanDetail->value,
                self::AdminPerusahaanDelete->value,
                self::AdminPerusahaanRestore->value,
                self::CustomerList->value,
                self::CustomerCreate->value,
                self::CustomerEdit->value,
                self::CustomerDetail->value,
                self::CustomerDelete->value,
                self::CustomerRestore->value,
                self::CustomerExport->value,
                self::CustomerImport->value,
                self::CustomerVerifyEmail->value,
                self::LanggananList->value,
                self::LanggananCreate->value,
                self::LanggananEdit->value,
                self::LanggananDetail->value,
                self::LanggananDelete->value,
                self::LanggananRestore->value,
                self::LanggananExport->value,
                self::LanggananImport->value,
                self::TagihanList->value,
                self::TagihanCreate->value,
                self::TagihanEdit->value,
                self::TagihanDetail->value,
                self::TagihanDelete->value,
                self::TagihanRestore->value,
                self::TagihanExport->value,
                self::TagihanImport->value,
                self::TagihanGenerate->value,
                self::InsentifList->value,
                self::InsentifCreate->value,
                self::InsentifEdit->value,
                self::InsentifDetail->value,
                self::InsentifDelete->value,
                self::InsentifRestore->value,
                self::InsentifExport->value,
                self::InsentifImport->value,
                self::RiwayatInsentifList->value,
                self::RiwayatInsentifCreate->value,
                self::RiwayatInsentifEdit->value,
                self::RiwayatInsentifDetail->value,
                self::RiwayatInsentifDelete->value,
                self::RiwayatInsentifRestore->value,
                self::RiwayatInsentifPersetujuan->value,
                self::RiwayatInsentifExport->value,
                self::RiwayatInsentifImport->value,
                self::RiwayatPembayaranList->value,
                self::RiwayatPembayaranCreate->value,
                self::RiwayatPembayaranEdit->value,
                self::RiwayatPembayaranDetail->value,
                self::RiwayatPembayaranDelete->value,
                self::RiwayatPembayaranRestore->value,
                self::RiwayatPembayaranPersetujuan->value,
                self::RiwayatPembayaranExport->value,
                self::RiwayatPembayaranImport->value,
                self::GangguanList->value,
                self::GangguanCreate->value,
                self::GangguanEdit->value,
                self::GangguanDetail->value,
                self::GangguanDelete->value,
                self::GangguanRestore->value,
                self::GangguanVerify->value,
                self::GangguanExport->value,
                self::GangguanImport->value,
                self::PerformaKaryawanList->value,
                self::PerformaKaryawanExport->value,
                self::KaryawanList->value,
                self::KaryawanCreate->value,
                self::KaryawanEdit->value,
                self::KaryawanDetail->value,
                self::KaryawanDelete->value,
                self::KaryawanRestore->value,
                self::KaryawanExport->value,
                self::KaryawanImport->value,
                self::RolePerusahaanOpList->value,
                self::RolePerusahaanOpCreate->value,
                self::RolePerusahaanOpEdit->value,
                self::RolePerusahaanOpDelete->value,
                self::RolePerusahaanOpRestore->value,
                self::AdminRolePerusahaanOpList->value,
                self::AdminRolePerusahaanOpCreate->value,
                self::AdminRolePerusahaanOpEdit->value,
                self::AdminRolePerusahaanOpDelete->value,
                self::AdminRolePerusahaanOpExport->value,
                self::AdminRolePerusahaanOpImport->value,
                self::RoleWebKaryawanList->value,
                self::RoleWebKaryawanCreate->value,
                self::RoleWebKaryawanEdit->value,
                self::RoleWebKaryawanDelete->value,
                self::RoleWebKaryawanRestore->value,
                self::AdminRoleWebKaryawanList->value,
                self::AdminRoleWebKaryawanCreate->value,
                self::AdminRoleWebKaryawanEdit->value,
                self::AdminRoleWebKaryawanDelete->value,
                self::AdminRoleWebKaryawanExport->value,
                self::AdminRoleWebKaryawanImport->value,
                self::KonfigurasiPerusahaanList->value,
                self::KonfigurasiPerusahaanCreate->value,
                self::KonfigurasiPerusahaanEdit->value,
                self::KonfigurasiPerusahaanDelete->value,
                self::KonfigurasiPerusahaanRestore->value,
                self::KonfigurasiPerusahaanExport->value,
                self::KonfigurasiPerusahaanImport->value,
            ],
            'karyawan_perusahaan' => [
                self::ProfilSayaList->value,
                self::KaryawanCustomerList->value,
                self::KaryawanCustomerCreate->value,
                self::KaryawanCustomerEdit->value,
                self::KaryawanCustomerDetail->value,
                self::KaryawanCustomerDelete->value,
                self::KaryawanCustomerRestore->value,
                self::KaryawanCustomerImport->value,
                self::KaryawanCustomerExport->value,
                self::KaryawanCustomerVerifyEmail->value,
                self::KaryawanLanggananList->value,
                self::KaryawanLanggananCreate->value,
                self::KaryawanLanggananEdit->value,
                self::KaryawanLanggananDetail->value,
                self::KaryawanLanggananDelete->value,
                self::KaryawanLanggananRestore->value,
                self::KaryawanLanggananImport->value,
                self::KaryawanLanggananExport->value,
                self::KaryawanTagihanList->value,
                self::KaryawanTagihanCreate->value,
                self::KaryawanTagihanEdit->value,
                self::KaryawanTagihanDetail->value,
                self::KaryawanTagihanDelete->value,
                self::KaryawanTagihanRestore->value,
                self::KaryawanTagihanImport->value,
                self::KaryawanTagihanExport->value,
                self::KaryawanTagihanGenerate->value,
                self::KaryawanInsentifList->value,
                self::KaryawanInsentifCreate->value,
                self::KaryawanInsentifEdit->value,
                self::KaryawanInsentifDetail->value,
                self::KaryawanInsentifDelete->value,
                self::KaryawanInsentifRestore->value,
                self::RiwayatInsentifList->value,
                self::RiwayatInsentifCreate->value,
                self::RiwayatInsentifEdit->value,
                self::RiwayatInsentifDetail->value,
                self::RiwayatInsentifDelete->value,
                self::RiwayatInsentifRestore->value,
                self::KaryawanRiwayatPembayaranList->value,
                self::KaryawanRiwayatPembayaranCreate->value,
                self::KaryawanRiwayatPembayaranEdit->value,
                self::KaryawanRiwayatPembayaranDetail->value,
                self::KaryawanRiwayatPembayaranDelete->value,
                self::KaryawanRiwayatPembayaranRestore->value,
                self::KaryawanRiwayatPembayaranPersetujuan->value,
                self::KaryawanRiwayatPembayaranExport->value,
                self::KaryawanRiwayatPembayaranImport->value,
                self::KaryawanGangguanList->value,
                self::KaryawanGangguanCreate->value,
                self::KaryawanGangguanEdit->value,
                self::KaryawanGangguanDetail->value,
                self::KaryawanGangguanDelete->value,
                self::KaryawanGangguanRestore->value,
                self::KaryawanGangguanResolve->value,
                self::KaryawanGangguanExport->value,
                self::KaryawanGangguanImport->value,
                self::KaryawanPaketList->value,
                self::KaryawanPaketDetail->value,
            ],
            default => [],
        };
    }

    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    public function action(): string
    {
        return explode('.', $this->value)[1] ?? '';
    }
}
