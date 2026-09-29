<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sales & Transport
    |--------------------------------------------------------------------------
    | Defaults used when logging sales orders and the transport requests that
    | move the sold items to the customer.
    */

    'currency' => env('SALES_CURRENCY', 'USD'),

    // Percentage applied to an order's subtotal unless the order overrides it.
    'default_tax_rate' => env('SALES_DEFAULT_TAX_RATE', 0),

    'order_prefix' => env('SALES_ORDER_PREFIX', 'SO'),

    'transport_prefix' => env('SALES_TRANSPORT_PREFIX', 'TR'),

];
