<?php
return [
    'default_title'       => 'Unwinded — Sip. Paint. Unwind.',
    'title_separator'     => ' — ',
    'default_description' => 'Fully personalised Sip & Paint experiences for corporate events, private celebrations, and public sessions across South Africa.',
    'ga_id'               => $_ENV['GOOGLE_ANALYTICS_ID']    ?? '',
    'gtm_id'              => $_ENV['GOOGLE_TAG_MANAGER_ID']  ?? '',
    'meta_pixel_id'       => $_ENV['META_PIXEL_ID']          ?? '',
];
