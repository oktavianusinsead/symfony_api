<?php
$loader->import("properties.php");

if (!getenv("REDIS_HOSTNAME") || !getenv("REDIS_HOSTPORT")) {
    $container->setParameter('redis_host',              "127.0.0.1"                                             );
    $container->setParameter('redis_port',              "6379"                                                  );
}
