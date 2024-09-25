<?php

namespace esuite\MIMBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class edotExtract
{

    /**
     * @var String
     * The Embed.ly API Key
     */
    private $key;

    public function __construct(ParameterBagInterface $parameterBag, /**
     * @var
     * The Logger
     */
    private readonly LoggerInterface $logger )
    {   
        $config = $parameterBag->get('edot.extract.config');
        $this->key = $config['embedly_api_key'];
    }

    /**
     * @param String  $url   The URL you wish to request
     *
     * @return Object
     */
    protected function get($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $data = json_decode(curl_exec($ch));
        curl_close($ch);

        return $data;
    }

    /**
     * @param String  $url  The URL you require meta for
     *
     * @return Object
     */
    public function url($url)
    {
        $meta = $this->get('http://api.embed.ly/1/oembed?key='.$this->key.'&url='.$url);
        if(!property_exists($meta, 'title')) {
            $this->logger->info('Adding Title');
            $meta->title = '';
        }
        if(!property_exists($meta, 'description')) {
            $this->logger->info('Adding description');
            $meta->description = '';
        }
        if(!property_exists($meta, 'thumbnail_url')) {
            $this->logger->info('Adding thumbnail_url');
            $meta->thumbnail_url = '';
        }
        return $meta;
    }


}
