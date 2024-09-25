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
$container->setParameter('aws_s3_bucket',               "study-temp-amazon"                     );

$container->setParameter('backup_url',                  "study-temp.test.com"                   );
$container->setParameter('backup_keypair_id',           "6xYRMR5JHTfnEvBQ"                      );

//resources
$container->setParameter('study_resource_bucket',       "bucket.study.resources"                );
$container->setParameter('cdn_bucket',                  "bucket.study.resources"                );

//ses
$container->setParameter('aws_ses_cc_email',            "appdev.testing@insead.edu"             );
$container->setParameter('aws_ses_from_email',          "appdev.testing@insead.edu"             );
$container->setParameter('aws_ses_review_cc_email',     "appdev.testing@insead.edu"             );

//sns
$container->setParameter('aws_access_key_id',           "WNz8vzQMc7kbrHEB"                      );
$container->setParameter('aws_secret_key',              "3rDLxAYXterHA9Aj"                      );
$container->setParameter('aws_region',                  "eu-west-1"                             );
$container->setParameter('aws_sns_platform_app_arn',    "test"                                  );

//myinsead
$container->setParameter('myinsead_api_provider_url',   "https://my-int.insead.edu"             );
$container->setParameter('aip_enabled',                 "YES"                                   );
