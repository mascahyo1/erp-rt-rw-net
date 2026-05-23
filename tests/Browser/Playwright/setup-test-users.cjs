#!/usr/bin/env node

const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const BASE_URL = 'http://erp-rt-rw-net.test';

function runPhp(command) {
    console.log(`[PHP] ${command.substring(0, 60)}...`);
    const result = execSync(`php artisan ${command}`, {
        cwd: 'C:/laragon/www/erp-rt-rw-net',
        encoding: 'utf8',
        stdio: ['pipe', 'pipe', 'pipe']
    });
    return result;
}

function createTestUsers() {
    console.log('\n=== Membuat User Test ===\n');

    try {
        // Check existing AdminCompany user
        console.log('[1] Cek user AdminCompany...');
        const existingAdmin = execSync('php artisan tinker --execute="echo App\\Models\\AdminCompany::first()?->email ?? \'TIDAK ADA\';"', {
            cwd: 'C:/laragon/www/erp-rt-rw-net',
            encoding: 'utf8'
        }).trim();
        console.log(`    Existing AdminCompany: ${existingAdmin}`);

        if (existingAdmin === 'TIDAK ADA') {
            console.log('[2] Membuat AdminCompany test user...');
            execSync('php artisan tinker --execute=\'
                $u = App\\Models\\AdminCompany::create([
                    "name" => "Test Admin",
                    "email" => "admin@perusahaan.rtrwnet.id",
                    "password" => bcrypt("password"),
                    "company_id" => App\\Models\\Company::first()->id ?? 1,
                    "is_active" => true
                ]);
                echo "Created: " . $u->email;
            \'', {
                cwd: 'C:/laragon/www/erp-rt-rw-net',
                encoding: 'utf8'
            });
            console.log('    Done!');
        }

        // Check existing AdminSaas user
        console.log('[3] Cek user AdminSaas...');
        const existingSaas = execSync('php artisan tinker --execute="echo App\\Models\\AdminSaas::first()?->email ?? \'TIDAK ADA\';"', {
            cwd: 'C:/laragon/www/erp-rt-rw-net',
            encoding: 'utf8'
        }).trim();
        console.log(`    Existing AdminSaas: ${existingSaas}`);

        if (existingSaas === 'TIDAK ADA') {
            console.log('[4] Membuat AdminSaas test user...');
            execSync('php artisan tinker --execute=\'
                $u = App\\Models\\AdminSaas::create([
                    "name" => "Test Admin SaaS",
                    "email" => "admin@saas.rtrwnet.id",
                    "password" => bcrypt("password"),
                    "is_active" => true
                ]);
                echo "Created: " . $u->email;
            \'', {
                cwd: 'C:/laragon/www/erp-rt-rw-net',
                encoding: 'utf8'
            });
            console.log('    Done!');
        }

        // Check Employee user
        console.log('[5] Cek user Karyawan...');
        const existingKaryawan = execSync('php artisan tinker --execute="echo App\\Models\\Employee::first()?->email ?? \'TIDAK ADA\';"', {
            cwd: 'C:/laragon/www/erp-rt-rw-net',
            encoding: 'utf8'
        }).trim();
        console.log(`    Existing Karyawan: ${existingKaryawan}`);

        if (existingKaryawan === 'TIDAK ADA') {
            console.log('[6] Membuat Karyawan test user...');
            execSync('php artisan tinker --execute=\'
                $u = App\\Models\\Employee::create([
                    "name" => "Test Karyawan",
                    "email" => "karyawan@rtrwnet.id",
                    "password" => bcrypt("password"),
                    "company_id" => App\\Models\\Company::first()->id ?? 1,
                    "is_active" => true
                ]);
                echo "Created: " . $u->email;
            \'', {
                cwd: 'C:/laragon/www/erp-rt-rw-net',
                encoding: 'utf8'
            });
            console.log('    Done!');
        }

        // Check Customer user
        console.log('[7] Cek user Pelanggan...');
        const existingPelanggan = execSync('php artisan tinker --execute="echo App\\Models\\Customer::first()?->email ?? \'TIDAK ADA\';"', {
            cwd: 'C:/laragon/www/erp-rt-rw-net',
            encoding: 'utf8'
        }).trim();
        console.log(`    Existing Pelanggan: ${existingPelanggan}`);

        if (existingPelanggan === 'TIDAK ADA') {
            console.log('[8] Membuat Pelanggan test user...');
            execSync('php artisan tinker --execute=\'
                $u = App\\Models\\Customer::create([
                    "name" => "Test Pelanggan",
                    "email" => "pelanggan@rtrwnet.id",
                    "password" => bcrypt("password"),
                    "company_id" => App\\Models\\Company::first()->id ?? 1,
                    "is_active" => true
                ]);
                echo "Created: " . $u->email;
            \'', {
                cwd: 'C:/laragon/www/erp-rt-rw-net',
                encoding: 'utf8'
            });
            console.log('    Done!');
        }

        console.log('\n=== Test Credentials ===');
        console.log('Admin Perusahaan: admin@perusahaan.rtrwnet.id / password');
        console.log('Admin SaaS: admin@saas.rtrwnet.id / password');
        console.log('Karyawan: karyawan@rtrwnet.id / password');
        console.log('Pelanggan: pelanggan@rtrwnet.id / password');
        console.log('\n');

    } catch (error) {
        console.error('[ERROR]', error.message);
    }
}

createTestUsers();