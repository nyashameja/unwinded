<?php
return [
    'max_size_mb'       => 20,
    'allowed_mime_types'=> ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_doc_types' => ['application/pdf'],
    'allowed_extensions'=> ['jpg', 'jpeg', 'png', 'webp'],
    'doc_extensions'    => ['pdf'],
    'image_sizes'       => [
        'thumb'  => ['w' => 400,  'h' => 400,  'crop' => true],
        'medium' => ['w' => 1000, 'h' => 1000, 'crop' => false],
        'large'  => ['w' => 1800, 'h' => 1800, 'crop' => false],
    ],
    'webp_quality'      => 82,
    'jpeg_quality'      => 85,
    'strip_exif_public' => true,
];
