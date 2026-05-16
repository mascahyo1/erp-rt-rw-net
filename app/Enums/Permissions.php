<?php

namespace App\Enums;

enum Permissions: string
{
    // ============================================================
    // OPERATOR SAAS
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
    // ADMIN PERUSAHAAN
    // ============================================================
    case PerusahaanSayaList = 'perusahaan-saya.list';
    case PaketList = 'paket.list';

    case CustomerList   = 'customer.list';
    case CustomerCreate = 'customer.create';
    case CustomerEdit   = 'customer.edit';
    case CustomerDetail = 'customer.detail';
    case CustomerDelete = 'customer.delete';
    case CustomerRestore = 'customer.restore';

    case LanggananList = 'langganan.list';
    case TagihanList = 'tagihan.list';
    case InsentifList = 'insentif.list';
    case RiwayatInsentifList = 'riwayat-insentif.list';
    case RiwayatPembayaranList = 'riwayat-pembayaran.list';

    case KaryawanList   = 'karyawan.list';
    case KaryawanCreate = 'karyawan.create';
    case KaryawanEdit   = 'karyawan.edit';
    case KaryawanDetail = 'karyawan.detail';
    case KaryawanDelete = 'karyawan.delete';
    case KaryawanRestore = 'karyawan.restore';

    case RolePerusahaanOpList = 'role-perusahaan-op.list';
    case AdminRolePerusahaanOpList = 'admin-role-perusahaan-op.list';
    case RoleWebKaryawanList = 'role-web-karyawan.list';
    case AdminRoleWebKaryawanList = 'admin-role-web-karyawan.list';
    case KonfigurasiPerusahaanList = 'konfigurasi-perusahaan.list';

    // ============================================================
    // KARYAWAN
    // ============================================================
    case ProfilSayaList = 'profil-saya.list';
    case KaryawanCustomerList = 'karyawan-customer.list';
    case KaryawanLanggananList = 'karyawan-langganan.list';
    case KaryawanTagihanList = 'karyawan-tagihan.list';
    case KaryawanInsentifList = 'karyawan-insentif.list';
    case KaryawanRiwayatPembayaranList = 'karyawan-riwayat-pembayaran.list';

    /**
     * Get all permissions for a given scope.
     */
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
                self::PerusahaanSayaList->value,
                self::PaketList->value,
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
                self::LanggananList->value,
                self::TagihanList->value,
                self::InsentifList->value,
                self::RiwayatInsentifList->value,
                self::RiwayatPembayaranList->value,
                self::KaryawanList->value,
                self::KaryawanCreate->value,
                self::KaryawanEdit->value,
                self::KaryawanDetail->value,
                self::KaryawanDelete->value,
                self::KaryawanRestore->value,
                self::RolePerusahaanOpList->value,
                self::AdminRolePerusahaanOpList->value,
                self::RoleWebKaryawanList->value,
                self::AdminRoleWebKaryawanList->value,
                self::KonfigurasiPerusahaanList->value,
            ],
            'karyawan_perusahaan' => [
                self::ProfilSayaList->value,
                self::KaryawanCustomerList->value,
                self::KaryawanLanggananList->value,
                self::KaryawanTagihanList->value,
                self::KaryawanInsentifList->value,
                self::KaryawanRiwayatPembayaranList->value,
            ],
            default => [],
        };
    }

    /**
     * Get the sidebar module slug for a permission.
     */
    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get the action for a permission.
     */
    public function action(): string
    {
        return explode('.', $this->value)[1] ?? '';
    }
}
