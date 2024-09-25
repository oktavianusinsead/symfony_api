<?php
//symfony environment
$container->setParameter('symfony_environment',         "prod"                                  );
$container->setParameter('secret',                      "P7gaDtF9Fc6Wcrj4EFdvrNhGBS8vEU3j"      );

//box

$container->setParameter('box_client_id',               "8YwuznZGM792cpcBTTFPvWmjeWPWL5Dy"      );
$container->setParameter('box_client_secret',           "hkAXpqkL3NVhjaZvh5GusS9KBAAsaRgj"      );
$container->setParameter('box_enterprise_id',           "1234567"                               );
$container->setParameter('box_master_id',               "1234567890"                            );
$container->setParameter('box_cert_id',                 "4PhaAjXj"                              );
$container->setParameter('box_cert_pass',               "DC43LZYW27TPekfB"                      );

//redis
$container->setParameter('redis_host',                  "127.0.0.1"                             );
$container->setParameter('redis_port',                  "6379"                                  );

//database

$container->setParameter('database_version',            ""                                      );


//s3
$container->setParameter('aws_s3_bucket',               "edot-temp-amazon"                     );

$container->setParameter('backup_url',                  "edot-temp.test.com"                   );
$container->setParameter('backup_keypair_id',           "6xYRMR5JHTfnEvBQ"                      );

//resources
$container->setParameter('edot_resource_bucket',       "bucket.edot.resources"                );
$container->setParameter('cdn_bucket',                  "bucket.edot.resources"                );

//ses
$container->setParameter('aws_ses_cc_email',            "appdev.testing@esuite.edu"             );
$container->setParameter('aws_ses_from_email',          "appdev.testing@esuite.edu"             );
$container->setParameter('aws_ses_review_cc_email',     "appdev.testing@esuite.edu"             );

//sns
$container->setParameter('aws_access_key_id',           "WNz8vzQMc7kbrHEB"                      );
$container->setParameter('aws_secret_key',              "3rDLxAYXterHA9Aj"                      );
$container->setParameter('aws_region',                  "eu-west-1"                             );
$container->setParameter('aws_sns_platform_app_arn',    "test"                                  );

//myesuite
$container->setParameter('myesuite_api_provider_url',   "https://my-int.esuite.edu"             );
$container->setParameter('aip_enabled',                 "YES"                                   );
