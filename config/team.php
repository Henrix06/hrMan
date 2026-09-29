<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Invitations
    |--------------------------------------------------------------------------
    | How long an emailed invitation link stays valid before it must be resent.
    */
    'invitation_expiry_days' => env('TEAM_INVITATION_EXPIRY_DAYS', 7),

];
