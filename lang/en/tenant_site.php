<?php

declare(strict_types=1);

return [
    'errors' => [
        'not_found' => 'The page you are looking for does not exist.',
        'no_tenant' => 'No active tenant context.',
    ],
    'nav' => [
        'customer_portal' => 'Customer Portal',
    ],
    'hero' => [
        'badge' => 'Trusted auto service',
        'whatsapp_cta' => 'WhatsApp',
        'book_cta' => 'Book appointment',
    ],
    'pricing' => [
        'starting_from' => 'Starting from :price SAR',
        'upon_inspection' => 'Price upon inspection',
        'sar' => 'SAR',
    ],
    'services' => [
        'title' => 'Our services',
        'subtitle' => 'A selection of services available at our center',
    ],
    'centers' => [
        'title' => 'Our branches',
        'subtitle' => 'Choose the branch closest to you',
        'main_badge' => 'Main',
    ],
    'why_us' => [
        'title' => 'Why choose us',
        'subtitle' => 'Quality, speed, and trust — in every visit',
        'cards' => [
            'quality' => [
                'title' => 'Certified quality',
                'body' => 'We uphold the highest quality standards in servicing your vehicle, using original parts and modern diagnostics.',
            ],
            'speed' => [
                'title' => 'Fast turnaround',
                'body' => 'A specialized team ensures your service is completed on time with step-by-step tracking.',
            ],
            'warranty' => [
                'title' => 'Trusted warranty',
                'body' => 'We back our services and replaced parts with a warranty for your complete peace of mind.',
            ],
        ],
    ],
    'cta' => [
        'title' => 'Need a service?',
        'body' => 'Reach out to us by phone or WhatsApp — we\'re happy to help.',
        'call' => 'Call us',
        'whatsapp' => 'WhatsApp',
        'portal_coming_soon' => 'Customer portal coming soon',
    ],
    'admin' => [
        'subtitle' => 'Manage the public website of your center — branding, contact, services and publication status.',
        'preview' => 'Preview public site',
        'save' => 'Save changes',
        'saved' => 'Website settings saved.',
        'remove_image' => 'Remove image',
        'confirm_remove_image' => 'Are you sure you want to remove this image?',
        'sections' => [
            'identity' => 'Identity',
            'branding' => 'Branding',
            'contact' => 'Contact information',
            'seo' => 'SEO & meta',
            'publish' => 'Publication',
        ],
        'fields' => [
            'site_name' => 'Site name',
            'tagline' => 'Tagline (one short line)',
            'logo' => 'Logo',
            'cover' => 'Cover image',
            'primary_color' => 'Primary color',
            'secondary_color' => 'Secondary color',
            'theme_key' => 'Theme',
            'theme_key_help' => 'Only the Default theme is shipped in this phase. Unknown values are coerced to Default at read time.',
            'contact_phone' => 'Phone',
            'contact_whatsapp' => 'WhatsApp number',
            'contact_email' => 'Email',
            'address' => 'Address',
            'social_links' => 'Social media links',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'status' => 'Publication status',
            'status_help' => 'Only Published sites are reachable at the public URL.',
        ],
        'status' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'unpublished' => 'Unpublished',
        ],
        'errors' => [
            'image_type' => 'Unsupported image type. Allowed: PNG, JPG or WebP.',
            'color_invalid' => 'The color must be a valid hex value (e.g. #4f46e5).',
            'social_url_invalid' => 'The :field URL must start with http:// or https://.',
        ],
    ],
];
