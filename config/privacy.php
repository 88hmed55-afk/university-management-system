<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Official identifier viewers
    |--------------------------------------------------------------------------
    | Official identifiers are classified as highly sensitive (docs/09 line 36).
    | This list is empty by default so access fails closed. The university must
    | approve explicit user IDs before anyone may reveal an identifier value;
    | access is never granted from a role title alone (docs/05 line 48).
    |
    */
    'identifier_viewer_user_ids' => [],

];
