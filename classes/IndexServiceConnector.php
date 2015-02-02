<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require realpath(dirname(__FILE__)) . '/../libraries/vendor/autoload.php';

class IndexServiceConnector
{

    private $indexService;
    private $indexServicePath;
    private $curl;

    /**
     * Connects to the IndexService and provides several functions to tigger index operations.
     * @param [type] $privateConfig
     */
    public function IndexServiceConnector($privateConfig)
    {
        $this->privateConfig = $privateConfig;
        $this->init();
        $this->indexService = $privateConfig->fulltextsearch->indexService;
        $this->indexServicePath = $privateConfig->fulltextsearch->indexServicePath;
    }

    public function init()
    {
        $this->curl = curl_init();
    }

    public function finish()
    {
        curl_close($this->curl);
    }

    /**
     * Triggers an event that re-indexes the given resource.
     * @param  [type] $resourceUri
     * @return [type]
     */
    public function triggerReindex($resourceUri, $classUri = null)
    {
        if ($classUri === null) {
            $url = $this->indexService . $this->indexServicePath . 'uri?resourceUri=' . $resourceUri;
        } else {
            $classQname = OntoWiki_Utils::compactUri($classUri);
            $url = $this->indexService . $this->indexServicePath . 'uri?resourceUri=' . $resourceUri . '&index=' . $classQname . '&objectType=' . $classQname;
        }
        $_owApp = OntoWiki::getInstance();
        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerDeleteResource reindexing resource: ' . $resourceUri);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        return curl_exec($this->curl);
    }


    /**
     * Triggers an event that deletes a resource with the given resource URI.
     * @param  [type] $resourceUri
     */
    public function triggerDeleteResource($resourceUri)
    {
        $url = $this->indexService . $this->indexServicePath . 'uri?resourceUri=' . $resourceUri;
        $_owApp = OntoWiki::getInstance();
        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerDeleteResource deleting resource: ' . $resourceUri);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        curl_exec($this->curl);
    }

    /**
     * Triggers an event which will create a new index from the given prefix uri.
     * @param  string $prefixUri
     * @return mixed curl response
     */
    public function triggerCreateIndex($prefixUri)
    {
        $_owApp = OntoWiki::getInstance();
        $translate = $_owApp->translate;
        if (isset($_owApp->selectedModel)) {
            $model = $_owApp->selectedModel;
            $resourceUri = Erfurt_Uri::getFromQnameOrUri($prefixUri, $model);

            $url = $this->indexService . $this->indexServicePath;
            $url .= 'clazz?index=' . $prefixUri;
            $url .= '&objectType=' . $prefixUri;
            $url .= '&resourceClazz=' . $resourceUri;

            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_HEADER, 0);
            curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($this->curl);
        } else {
            $response = $translate->_('error: please choose a knowledge base');
        }
        return $response;
    }

    public function triggerDeleteIndex($indexName)
    {
        $url = $this->indexService . $this->indexServicePath . 'delete?index=' . $indexName;
        $_owApp = OntoWiki::getInstance();
        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerDeleteIndex->url: ' . $url);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($this->curl);
        return $response;
    }

//    public function triggerReindexClass($indexName = null)
//    {
//        if ($indexName !== null) {
//            $url = $this->indexService . $this->indexServicePath . 'reindex?index=' . $indexName;
//        } else {
//            $url = $this->indexService . $this->indexServicePath . 'reindex';
//        }
//        $_owApp = OntoWiki::getInstance();
//        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerReindexClass->indexName: ' . $indexName);
//        curl_setopt($this->curl, CURLOPT_URL, $url);
//        curl_setopt($this->curl, CURLOPT_HEADER, 0);
//        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
//        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
//        OntoWiki::getInstance()->logger->info('triggerReindexClass: ' . $url);
//        $response = curl_exec($this->curl);
//        return $response;
//    }


    public function triggerReindexClass($indexname)
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;

        $logger->debug('triggerReindexClass: ' . $indexname);
//        $url = 'http://' . $this->indexService . $this->indexServicePath . 'reindex?indexname=' . $indexname;
        $url = 'http://' . $this->indexService . $this->indexServicePath . 'reindex';

        $client = new \Guzzle\Http\Client();

//        $response = $client->get($url);
//        $logger->debug('url:' . $url);
//        $request = $client->createRequest('GET', $url);
//        $request->setPort('8080');
//        $response = $client->send($request);
        $classes = $this->privateConfig->fulltextsearch->classes->toArray();

        $request = $client->createRequest('POST', $url);
        $postBody = $request->getBody();

        $postBody->setField('indexname', $indexname);
        $postBody->setField('classes', $classes);

        $response = $client->send($request);

        $logger->debug('triggerReindexClass:response: ' . $response);


        return $response;


//        $_owApp = OntoWiki::getInstance();
//        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerReindexClass');

    }


    public function triggerFullreindex()
    {
//        $this->triggerReindexClass();
    }
}
