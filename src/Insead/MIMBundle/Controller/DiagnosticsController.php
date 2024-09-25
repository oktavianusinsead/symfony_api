<?php

namespace Insead\MIMBundle\Controller;

use Exception;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use LightSaml\Meta\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;

use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Diagnostics")]
class DiagnosticsController extends BaseController
{

    #[Get("/diagnostics")]
    #[OA\Response(
        response: 200,
        description: "Handler function to check if MIMAPI can access other services/systems")]
    public function getDiagnosticsAction(Request $request)
    {
        $this->setLogUuid($request);

        $responseObj = [];
        $responseObj["environment"] = $this->getEnvironment();
        $responseObj["datasources"]["main"] = $this->getDBInfo();
        $responseObj["cert_db"] = $this->getCertDBInfo();

        //redis
        if( class_exists("Redis") ) {
            $responseObj["redis"] = [
                "server_status" => $this->getServerStatus()
            ];
        } else {
            $responseObj["redis"] = "Redis class not found";
        }

        return $responseObj;
    }

    #[Get("/server-status")]
    #[OA\Response(
        response: 200,
        description: "Handler function to check if MIMAPI has any maintenance message")]
    public function getServerStatusAction(Request $request)
    {
        $this->setLogUuid($request);

        $responseObj = [
            "message" => ""
        ];

        if( class_exists("Redis") ) {
            $this->log($this->getServerStatus());
            $responseObj["message"] = $this->getServerStatus();
        }

        return $responseObj;
    }

    #[Post("/server-status")]
    #[OA\Response(
        response: 200,
        description: "Handler function to set maintenance message")]
    public function setServerStatusAction(Request $request)
    {
        $this->setLogUuid($request);

        $message = $request->get("message");

        $responseObj = [
            "message" => ""
        ];

        if( class_exists("Redis") ) {

            $this->setServerStatus($message);
            $responseObj["message"] = $this->getServerStatus();

        }

        return $responseObj;
    }

    #[Get("/ping/{token}")]
    #[OA\Response(
        response: 200,
        description: "Handler function for ELB Health Check")]
    public function getHealthStatusAction($token)
    {
        if ($token === 'c3R1ZHlwaW5nMjAyMA=='){
            return ['Pong!'];
        } else {
            throw new ResourceNotFoundException();
        }
    }

    private function getEnvironment()
    {
        //environment
        if( getenv("AWS_ENVIRONMENT") ) {
            $msg = getenv("AWS_ENVIRONMENT");
        } else {
            $msg = "n/a";
        }

        return $msg;
    }

    private function getDBInfo()
    {
        //db check
        try {
            $this->listAll("Role");
            $msg = "OK";
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }

        return $msg;
    }

    private function getCertDBInfo()
    {
        if( file_exists( $this->baseParameterBag->get('database_cert') ) ) {
            $msg = "OK";
        } else {
            $msg = "Not found";
        }
        return $msg;
    }

    private function getServerStatus()
    {
        $msg = "";
        //retrieve the saved value
        try {
            $env = $this->baseParameterBag->get('kernel.environment');
            $redis = new \Redis();
            $redis->connect($this->baseParameterBag->get('redis_host'), $this->baseParameterBag->get('redis_port'));
            $redis->setOption(\Redis::OPT_PREFIX, "study:$env:");

            if ($redis->get('server_status')) {
                $msg = $redis->get('server_status');
            } else {
                if ($redis->ping()) {
                    $msg = "Redis ok";
                } else {
                    $msg = "Redis unable to ping";
                }
            }
        } catch (Exception $e) {
            //do nothing
            $msg = $e->getMessage();
        }

        return $msg;
    }

    private function setServerStatus($message)
    {
        //set the value
        try {
            $env = $this->baseParameterBag->get('kernel.environment');
            $redis = new \Redis();
            $redis->connect($this->baseParameterBag->get('redis_host'), $this->baseParameterBag->get('redis_port'));
            $redis->setOption(\Redis::OPT_PREFIX, "study:$env:");

            $redis->set('server_status',$message);
        } catch (Exception) {
            //do nothing
        }
    }

    #[OA\Parameter(name: "url", description: "API Endpoint URL", in: "query", schema: new OA\Schema(type: "String"))]
    #[OA\Parameter(name: "headers", description: "Headers", in: "query", schema: new OA\Schema(type: "array"))]
    #[OA\Parameter(name: "method ", description: "Request Method - GET/POST/PUT/DELETE", in: "query", schema: new OA\Schema(type: "String"))]
    #[OA\Response(
        response: 200,
        description: "Setup curl resource.")]
    private function initCurl($url, $headers, $method)
    {
        $curl = curl_init();
        $headers_encode = ['Content-Type: application/json'];

        foreach ($headers as $key => $value) {
            array_push($headers_encode, $key . ': ' . $value);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        if($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        } elseif($method == 'GET') {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers_encode);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //timeout
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 10000);

        return $curl;
    }

    #[OA\Parameter(name: "curl", description: "CURL object with url, request headers and body set", in: "query", schema: new OA\Schema(type: "Resource"))]
    #[OA\Response(
        response: 200,
        description: "Function to execute a curl command")]
    private function sendTestRequest($curl) {
        $response           = curl_exec($curl);

        if (!$response || empty($response)) {
            $curlError       = curl_error($curl);
            return $curlError;
        }

        return "OK";
    }
    
    #[OA\Parameter(name: "val", description: "string to clean", in: "query", schema: new OA\Schema(type: "Resource"))]
    #[OA\Response(
        response: 200,
        description: "Function to strip out sensitive URL string")]
    private function cleanSensitiveStrings(string $val): string
    {
        $newResponse = "";
        foreach( explode(" ",$val) as $responseWords ) {
            if( str_contains($responseWords, '.insead.')
                || str_contains($responseWords, '.amazonaws.')
                || str_contains($responseWords, '.elasticbeanstalk.')
            ) {
                $responseWords = "...";
            }
            $newResponse = $newResponse . $responseWords . " ";
        }
        return $newResponse;
    }
}
