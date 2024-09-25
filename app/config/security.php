<?php
$AWS_ENVIRONMENT     = getenv("AWS_ENVIRONMENT") ? getenv("AWS_ENVIRONMENT") : 'dev';
$AWS_URL_ENVIRONMENT = $AWS_ENVIRONMENT == 'awsdev' ? 'dev' : $AWS_ENVIRONMENT;
$DIAG_PASSWORD       = getenv("DIAG_PASSWORD") ? getenv("DIAG_PASSWORD") : 'gwo7yqp984gnyp9q834yg9q83y4-qg98ygn4a';
$CRON_PASSWORD       = getenv("CRON_PASSWORD") ? getenv("CRON_PASSWORD") : 'gwo7yqp984gnyp9q834yg9q83y4-qg98ygn4a';
$ESB_PASSWORD        = getenv("ESB_PASSWORD")  ? getenv("ESB_PASSWORD") : 'gwo7yqp984gnyp9q834yg9q83y4-qg98ygn4a';
$COMPANION_PASSWORD  = getenv("COMPANION_PASSWORD")  ? getenv("COMPANION_PASSWORD") : '$2y$15$aUu0pF8fbVOzLVCOKKdiWesGQbjB.0O7waNKkseSmsfYoTRMTOS5O';
$APIDOC_PASSWORD     = getenv("APIDOC_PASSWORD")  ? getenv("APIDOC_PASSWORD") : 'JDJ5JDE1JEtZMHdMajhnQm9nRkRJbzNhbElOby5nLm9PLzBBUndUSWh0dEZiTFRtYmRJS0Z2YVJsaUhL';

$container->loadFromExtension('security', array(
    'providers' => array(
        'in_memory' => array(
            'memory' => array(
                'users' => array(
                    'studysuper' => array(
                        'password' => base64_decode($DIAG_PASSWORD),
                        'roles' => 'ROLE_DIAG'
                    ),
                    'cronuser' => array(
                        'password' => base64_decode($CRON_PASSWORD),
                        'roles' => 'ROLE_CRON'
                    ),
                    'esb' => array(
                        'password' => base64_decode($ESB_PASSWORD),
                        'roles' => 'ROLE_ESB'
                    ),
                    'companionuser' => array(
                        'password' => base64_decode($COMPANION_PASSWORD),
                        'roles' => 'ROLE_DIAG'
                    ),
                    'studyapidocumentor' => array(
                        'password' => base64_decode($APIDOC_PASSWORD),
                        'roles' => 'ROLE_DOCUMENTATION'
                    ),
                )
            )
        ),
        'saml_user_provider' => array(
            'id' => 'redis.saml.user.provider'
        )
    ),

    'firewalls' => array(
        'api_documentation' => array(
            'pattern'  => '^/api/doc',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'diagnostics' => array(
            'pattern'  => '^/api/(.*?)/diagnostics',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'forceMaintenanceOff' => array(
            'pattern'  => '^/api/(.*?)/forceMaintenanceOff',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'profilebookTimeStampUpdate' => array(
            'pattern'  => '^/api/(.*?)/profile-books/(.*?)/timestamp',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'cron-coursebackup' => array(
            'pattern'  => '^/api/(.*?)/course-backup',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'pending-attachment' => array(
            'pattern'  => '^/api/(.*?)/pending-attachments',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'cron-huddle-user' => array(
            'pattern'  => '^/api/(.*?)/cron-huddle-users',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'profiles_esb' => array(
            'pattern'  => '^/api/(.*?)/aip/bulk/users',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'companion' => array(
            'pattern'  => '^/api/(.*?)/companion/(.*?)',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'organization_esb' => array(
            'pattern'  => '^/api/(.*?)/aip/bulk/organizations',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'barco_user_psoft_cleaner' => array(
            'pattern'  => '^/api/(.*?)/barco-update-user-peoplesoft_id',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'archive' => array(
            'pattern'  => '^/api/(.*?)/archive/(.*?)',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'copy-programme' => array(
            'pattern'  => '^/api/(.*?)/copy-programme/initiate',
            'lazy' => true,
            'http_basic' => true,
            'provider' => 'in_memory'
        ),
        'main' => array(
            'custom_authenticators' => array(
                'ligntsaml_authenticator_provider'
            ),
            'logout' => array(
                'path' => 'lightsaml_sp.logout'
            ),
            'lazy' => true,
            'provider' => 'saml_user_provider'
        ),
        'api' => array (
            'pattern' => '^/api/(.*?)/.*',
            'stateless' => true,
            'lazy' => true
        )
    ),

    'access_control' => array(
        array('path' => '^/sso/logout', 'role'                                     => 'PUBLIC_ACCESS', 'requires_channel' => 'https'),
        array('path' => '^/sso', 'role'                                            => 'ROLE_USER', 'requires_channel' => 'https'),
        array('path' => '^/sso/failure', 'role'                                    => 'ROLE_USER', 'requires_channel' => 'https'),
        array('path' => '^/saml/sp', 'role'                                        => 'PUBLIC_ACCESS', 'requires_channel' => 'https'),
        array('path' => '^/api/doc', 'role'                                        => 'ROLE_DOCUMENTATION', 'requires_channel' => 'https'),
        array('path' => '^/bundles/nelmioapidoc', 'role'                           => 'PUBLIC_ACCESS', 'requires_channel' => 'https'),
        array('path' => '^/api/(.*?)/token', 'role'                                => 'PUBLIC_ACCESS'),
        array('path' => '^/api/(.*?)/login', 'role'                                => 'PUBLIC_ACCESS'),
        array('path' => '^/api/(.*?)/server-status', 'role'                        => 'PUBLIC_ACCESS'),
        array('path' => '^/api/cleanup', 'role'                                    => 'PUBLIC_ACCESS'),
        array('path' => '^/api/(.*?)/ping', 'role'                                 => 'PUBLIC_ACCESS'),
        array('path' => '^/api/(.*?)/diagnostics', 'role'                          => 'ROLE_DIAG'),
        array('path' => '^/api/(.*?)/forceMaintenanceOff', 'role'                  => 'ROLE_DIAG'),
        array('path' => '^/api/(.*?)/profile-books/(.*?)/timestamp', 'role'        => 'ROLE_DIAG'),
        array('path' => '^/api/(.*?)/companion/(.*?)', 'role'                      => 'ROLE_DIAG'),
        array('path' => '^/api/(.*?)/copy-programme/initiate', 'role'              => 'ROLE_DIAG'),
        array('path' => '^/api/(.*?)/course-backup', 'role'                        => 'ROLE_CRON'),
        array('path' => '^/api/(.*?)/pending-attachments', 'role'                  => 'ROLE_CRON'),
        array('path' => '^/api/(.*?)/archive/user-tokens', 'role'                  => 'ROLE_CRON'),
        array('path' => '^/api/(.*?)/cron-huddle-users', 'role'                    => 'ROLE_CRON'),
        array('path' => '^/api/(.*?)/barco-update-user-peoplesoft_id', 'role'      => 'ROLE_CRON'),
        array('path' => '^/api/(.*?)/aip/bulk/users', 'role'                       => 'ROLE_ESB'),
        array('path' => '^/api/(.*?)/aip/bulk/organizations', 'role'               => 'ROLE_ESB'),
    )
));
