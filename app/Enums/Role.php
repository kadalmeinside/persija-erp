<?php

namespace App\Enums;

/**
 * Enum untuk nama-nama Role yang digunakan di sistem.
 *
 * Gunakan enum ini di seluruh codebase sebagai pengganti hardcoded string.
 * Contoh: Role::FINANCE->value, Role::financeRoles()
 *
 * Role yang ada di database:
 *   Super Admin, Manajer Departemen, Staf Finance, Staf,
 *   Direktur, Finance, HR Staff, IT Support
 */
enum Role: string
{
    // =========================================================================
    // Active Roles (Exist in Database)
    // =========================================================================

    case SUPER_ADMIN         = 'Super Admin';
    case MANAJER_DEPARTEMEN  = 'Manajer Departemen';
    case STAF_FINANCE        = 'Staf Finance';
    case STAF                = 'Staf';
    case DIREKTUR            = 'Direktur';
    case FINANCE             = 'Finance';
    case HR_STAFF            = 'HR Staff';
    case IT_SUPPORT          = 'IT Support';

    // =========================================================================
    // Planned / Future Roles (Not yet in Database — reserved for expansion)
    // =========================================================================

    case FINANCE_MANAGER     = 'Finance Manager';
    case HR_MANAGER          = 'HR Manager';

    // =========================================================================
    // Helper: Role Groups (kembalikan array of string value)
    // =========================================================================

    /**
     * Role yang memiliki akses ke modul Finance (pembayaran, laporan, dll).
     *
     * @return string[]
     */
    public static function financeRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::FINANCE->value,
            self::FINANCE_MANAGER->value,
            self::STAF_FINANCE->value,
        ];
    }

    /**
     * Role yang memiliki akses ke modul HR (cuti, kalender karyawan, dll).
     *
     * @return string[]
     */
    public static function hrRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::HR_MANAGER->value,
            self::HR_STAFF->value,
        ];
    }

    /**
     * Role yang memiliki akses ke manajemen IT Support / Ticket.
     *
     * @return string[]
     */
    public static function itRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::IT_SUPPORT->value,
        ];
    }

    /**
     * Role Administrasi tingkat atas (akses luas ke seluruh sistem).
     *
     * @return string[]
     */
    public static function adminRoles(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::DIREKTUR->value,
        ];
    }
}
