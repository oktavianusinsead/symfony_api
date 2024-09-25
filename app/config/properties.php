<?php
//symfony environment
$container->setParameter('symfony_environment',         getenv("AWS_ENVIRONMENT")                             );
$container->setParameter('secret',                      getenv("SYMFONY_SECRET")                              );

// API Connect
$container->setParameter('study_api_id',               base64_decode( getenv("STUDY_API_ID"))               );
$container->setParameter('study_api_secret',           base64_decode( getenv("STUDY_API_SECRET"))               );

//box
$container->setParameter('box_client_id',               base64_decode( getenv("BOX_CLIENT_ID"))               );
$container->setParameter('box_client_secret',           base64_decode( getenv("BOX_CLIENT_SECRET"))           );
$container->setParameter('box_enterprise_id',           base64_decode( getenv("BOX_ENTERPRISE_ID"))           );
$container->setParameter('box_master_id',               base64_decode( getenv("BOX_MASTER_ID"))               );
$container->setParameter('box_cert_id',                 base64_decode( getenv("BOX_CERT_ID"))                 );
$container->setParameter('box_cert_pass',               base64_decode( getenv("BOX_CERT_PASS"))               );

//redis
$container->setParameter('redis_host',                  base64_decode( getenv("REDIS_HOSTNAME"))              );
$container->setParameter('redis_port',                  base64_decode( getenv("REDIS_HOSTPORT"))              );

//database
$container->setParameter('database_driver',             'pdo_mysql');

$container->setParameter('database_host',               base64_decode( getenv("RDS_HOSTNAME"))                );
$container->setParameter('database_port',               base64_decode( getenv("RDS_HOSTPORT"))                );
$container->setParameter('database_name',               base64_decode( getenv("RDS_DATABASE"))                );
$container->setParameter('database_user',               base64_decode( getenv("RDS_USERNAME"))                );
$container->setParameter('database_password',           base64_decode( getenv("RDS_PASSWORD"))                );

$container->setParameter('database_version',            base64_decode( getenv("RDS_VERSION"))                 );

$databaseCert = $container->getParameter('database_cert');
$databaseOpts = null;
if ($databaseCert != '') {
    $databaseOpts = [PDO::MYSQL_ATTR_SSL_CA => $databaseCert];
}
$container->setParameter('database_opts', $databaseOpts);

//s3
$container->setParameter('aws_s3_bucket',               base64_decode( getenv("AWS_S3_BUCKET"))               );

$container->setParameter('backup_url',                  base64_decode( getenv("BACKUP_URL"))                  );
$container->setParameter('backup_keypair_id',           base64_decode( getenv("BACKUP_KEYPAIR_ID"))           );

//resources
$container->setParameter('study_resource_bucket',       base64_decode( getenv("STUDY_RESOURCE_BUCKET"))       );
$container->setParameter('cdn_bucket',                  base64_decode( getenv("CDN_BUCKET"))                  );

//ses
$container->setParameter('aws_ses_cc_email',            base64_decode( getenv("AWS_SES_CC_EMAIL"))            );
$container->setParameter('aws_ses_from_email',          base64_decode( getenv("AWS_SES_FROM_EMAIL"))          );
$container->setParameter('aws_ses_review_cc_email',     base64_decode( getenv("AWS_SES_REVIEW_CC_EMAIL"))     );

//sns
$container->setParameter('aws_access_key_id',           base64_decode( getenv("AWS_ACCESS_KEY_ID"))           );
$container->setParameter('aws_secret_key',              base64_decode( getenv("AWS_SECRET_KEY"))              );
$container->setParameter('aws_region',                  base64_decode( getenv("AWS_REGION"))                  );
$container->setParameter('aws_sns_platform_app_arn',    base64_decode( getenv("AWS_SNS_PLATFORM_APP_ARN"))    );

//myinsead
$container->setParameter('myinsead_api_provider_url',   base64_decode( getenv("MYINSEAD_URL"))                );
$container->setParameter('myinsead_api_app_secret_key', base64_decode( getenv("MYINSEAD_APP_SECRET_KEY"))     );
$container->setParameter('myinsead_api_secret_key',     base64_decode( getenv("MYINSEAD_SECRET_KEY"))         );
$container->setParameter('myinsead_api_client_id',      base64_decode( getenv("MYINSEAD_CLIENT_ID"))          );
$container->setParameter('myinsead_api_iss',            base64_decode( getenv("MYINSEAD_ISS"))                );
$container->setParameter('myinsead_api_aud',            base64_decode( getenv("MYINSEAD_AUD"))                );
$container->setParameter('myinsead_api_scope',          base64_decode( getenv("MYINSEAD_SCOPE"))              );

//ad webservice
$container->setParameter('adws_url',                    base64_decode( getenv("ADWS_URL"))                    );
$container->setParameter('adws_username',               base64_decode( getenv("ADWS_USERNAME"))               );
$container->setParameter('adws_password',               base64_decode( getenv("ADWS_PASSWORD"))               );

//study
$container->setParameter('study_weburl',                base64_decode( getenv("STUDY_WEBURL"))                );
$container->setParameter('study_adminurl',              base64_decode( getenv("STUDY_ADMINURL"))                );
$container->setParameter('study_super',                 base64_decode( getenv("STUDY_SUPER"))                 );

//adfs
$container->setParameter('adfs_entity_id',              base64_decode( getenv("ADFS_ENTITY_ID"))              );
$container->setParameter('adfs_admin_entity_id',        base64_decode( getenv("ADFS_ADMIN_ENTITY_ID"))              );
$container->setParameter('adfs_ios_entity_id',          base64_decode( getenv("ADFS_IOS_ENTITY_ID"))              );

//vanilla forums
$container->setParameter('vanilla_base_url',            base64_decode( getenv("VANILLA_BASE_URL"))            );
$container->setParameter('vanilla_api_url',             base64_decode( getenv("VANILLA_API_URL"))             );
$container->setParameter('vanilla_master_token',        base64_decode( getenv("VANILLA_MASTER_TOKEN"))        );
$container->setParameter('vanilla_conversation_limit',  base64_decode( getenv("VANILLA_CONVERSATION_LIMIT"))  );
$container->setParameter('vanilla_category',            base64_decode( getenv("VANILLA_CATEGORY"))  );

//sync MyINSEAD
$container->setParameter('sync_myinsead',               base64_decode( getenv("SyncMyINSEAD"))  );

//aip config
$container->setParameter('aip_enabled',                 base64_decode( getenv("AIP_ENABLED"))  );
$container->setParameter('aip_base_url',                base64_decode( getenv("AIP_BASE_URL"))  );
$container->setParameter('aip_client_id',               base64_decode( getenv("AIP_CLIENT_ID"))  );
$container->setParameter('aip_client_secret',           base64_decode( getenv("AIP_CLIENT_SECRET"))  );

$container->setParameter('barco_weconnect_api_key',     base64_decode( getenv("BARCO_WECONNECT_API_KEY"))  );
$container->setParameter('barco_weconnect_api_url',     base64_decode( getenv("BARCO_WECONNECT_API_URL"))  );

$container->setParameter('companion_password',          getenv("COMPANION_PASSWORD"));

$container->setParameter('adfs_idp_url',                base64_decode( getenv("SSO_IDP_URL"))  );
$container->setParameter('saml_logout',                 base64_decode( getenv("SLO_IDP_URL"))  );
$container->setParameter('idp_logout_landing_page',     base64_decode( getenv("SLO_IDP_LANDING_URL"))  );

$container->setParameter('aip_person_enabled',          base64_decode( getenv("AIP_PERSON_ENABLED"))  );
$container->setParameter('aip_person_base_url',         base64_decode( getenv("AIP_PERSON_BASE_URL"))  );
$container->setParameter('aip_person_client_id',        base64_decode( getenv("AIP_PERSON_CLIENT_ID"))  );
$container->setParameter('aip_person_client_secret',    base64_decode( getenv("AIP_PERSON_CLIENT_SECRET"))  );
$container->setParameter('aip_person_keys',             unserialize(base64_decode(getenv("AIP_PERSON_KEYS")))  );
