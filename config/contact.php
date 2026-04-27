<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact form – inbound address
    |--------------------------------------------------------------------------
    |
    | Public contact submissions are sent to this address. Set MAIL_CONTACT_TO
    | (primary). Optional alias: CONTACT_MAIL_TO. Falls back to MAIL_FROM_ADDRESS.
    |
    */
    'mail_to' => env('MAIL_CONTACT_TO', env('CONTACT_MAIL_TO', env('MAIL_FROM_ADDRESS', null))),

];
