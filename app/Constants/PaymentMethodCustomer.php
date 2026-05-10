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
                            'name' => 'Tunai'
                        ],
                        [
                            'slug' => 'bank_transfer',
                            'name' => 'Bank Transfer'
                        ]
                    ]
                ],
                [
                    'slug' => 'midtrans',
                    'name' => 'Midtrans',
                    'methods' => [
                        [
                            'slug' => 'gopay',
                            'name' => 'Gopay'
                        ],
                        [
                            'slug' => 'gopay_dynamic_qris',
                            'name' => 'Gopay Dynamic QRIS'
                        ],
                        [
                            'slug' => 'cimb_niaga_va',
                            'name' => 'Bank CIMB Niaga VA'
                        ],
                        [
                            'slug' => 'bsi_va',
                            'name' => 'Bank BSI VA'
                        ],
                        [
                            'slug' => 'bni_va',
                            'name' => 'Bank BNI VA'
                        ],
                        [
                            'slug' => 'bri_va',
                            'name' => 'Bank BRI VA'
                        ],
                        [
                            'slug' => 'permata_va',
                            'name' => 'Bank Permata VA'
                        ],
                        [
                            'slug' => 'mandiri_va',
                            'name' => 'Bank Mandiri VA'
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