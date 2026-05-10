<?php

namespace App\Constants;

class PaymentMethodCustomer
{
    private $list = [];

    public function __construct()
    {
        $this->list = [
            "Provider" => [
                [
                    'slug' => 'internal',
                    'name' => 'Internal',
                    'methods' => [
                        [
                            'slug' => 'tunai',
                            'name' => 'Tunai',
                            'show_to_customer' => false,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => true,
                        ],
                        [
                            'slug' => 'bank_transfer',
                            'name' => 'Bank Transfer',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => true,
                        ]
                    ]
                ],
                [
                    'slug' => 'midtrans',
                    'name' => 'Midtrans',
                    'methods' => [
                        [
                            'slug' => 'gopay',
                            'name' => 'Gopay',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'gopay_dynamic_qris',
                            'name' => 'Gopay Dynamic QRIS',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'cimb_niaga_va',
                            'name' => 'Bank CIMB Niaga VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'bsi_va',
                            'name' => 'Bank BSI VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'bni_va',
                            'name' => 'Bank BNI VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'bri_va',
                            'name' => 'Bank BRI VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'permata_va',
                            'name' => 'Bank Permata VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ],
                        [
                            'slug' => 'mandiri_va',
                            'name' => 'Bank Mandiri VA',
                            'show_to_customer' => true,
                            'show_to_admin_perusahaan' => true,
                            'show_to_web_karyawan' => true,
                            'wajib_input_bukti_pembayaran' => false,
                        ]
                    
                    ]
                ]
            ]
        ];
    }

    public function getList()
    {
        return $this->list;
    }
}