<?php
$container->loadFromExtension('security', ['providers' => ['in_memory' => ['memory' => []]], 'firewalls' => ['api' => ['pattern' => '^/api/(.*?)/.*', 'stateless' => true, 'lazy' => true]]]);
