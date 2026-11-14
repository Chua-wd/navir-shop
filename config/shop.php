<?php

// Shop settings for the cart and checkout.
return [

    // Orders from this subtotal ship for free; below it the fee is added.
    'free_shipping_from' => 200000,
    'shipping_fee' => 15000,

    // Bank transfer details shown on the payment page (example data, change before real use).
    'bank' => [
        'name' => 'BCA',
        'account' => '123 456 7890',
        'holder' => 'PT Navir Indonesia',
    ],

    // Public demo accounts for visitors and interviewers. Shown on the login page.
    // They can look at everything, but changes are turned off (see DemoReadOnly middleware),
    // except shopping and moving an order's status. Create them on the live site with this password.
    'demo' => [
        'password' => 'navir-demo-2026',
        'accounts' => [
            'demo.admin@navir.test' => ['name' => 'Demo Admin', 'role' => 'admin'],
            'demo.user@navir.test' => ['name' => 'Demo User', 'role' => 'user'],
        ],
    ],

];
