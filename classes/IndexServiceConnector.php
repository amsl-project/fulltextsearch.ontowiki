<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require realpath(dirname(__FILE__)) . '/../libraries/vendor/autoload.php';
require_once realpath(dirname(__FILE__)) . '/ElasticsearchUtils.php';
require_once realpath(dirname(__FILE__)) . '/ElasticsearchHelper.php';

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
    public function triggerReindex($resourceUri, $model, $class)
    {
        $url = 'http://' . $this->indexService . $this->indexServicePath . 'uri';

//            $classQname = OntoWiki_Utils::compactUri($classUri);
        $data = array('resourceUri' => $resourceUri, 'index' => $model, 'objectType' => $class, 'graphUri' => $model);
        $_owApp = OntoWiki::getInstance();
        $_owApp->getCustomLogger('fulltextsearch')->debug('Fulltextsearch->IndexServiceConnector->triggerReindex reindexing resource: ' . $resourceUri);
        $response = Requests::request($url, array(), $data, Requests::GET);

        return $response;

    }


    /**
     * Triggers an event that deletes a resource with the given resource URI.
     * @param  [type] $resourceUri
     */
    public function triggerDeleteResource($resourceUri, $model)
    {
        $url = 'http://' . $this->indexService . $this->indexServicePath . 'uri';

//        $url = $this->indexService . $this->indexServicePath . 'uri?resourceUri=' . $resourceUri;
        $_owApp = OntoWiki::getInstance();
        $_owApp->getCustomLogger('fulltextsearch')->debug('Fulltextsearch->IndexServiceConnector->triggerDeleteResource deleting resource: ' . $resourceUri);
        $data = array('resourceUri' => $resourceUri, 'graphUri' => $model);
        $response = Requests::request($url, array(), $data, Requests::DELETE);

        return $response;

//        curl_setopt($this->curl, CURLOPT_URL, $url);
//        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
//        curl_setopt($this->curl, CURLOPT_HEADER, 0);
//        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
//        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
//        curl_exec($this->curl);
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

    /**
     * Delets an index and all of its documents.
     *
     * @param $indexname
     * @return Requests_Response
     */
    public function triggerDeleteIndex($indexname)
    {
        $url = 'http://' . $this->indexService . $this->indexServicePath . 'delete';
        $_owApp = OntoWiki::getInstance();
        $_owApp->logger->debug('Fulltextsearch->IndexServiceConnector->triggerDeleteIndex->url: ' . $url);

        $data = array('index' => $indexname);
        $response = Requests::request($url, array(), $data, Requests::DELETE);
        return $response;
    }

    /**
     * Reindexes an existing index
     *
     * @param $indexname
     * @return array
     */
    public function triggerReindexClass($indexname)
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger('fulltextsearch');

        $logger->debug('triggerReindexClass: ' . $indexname);

        $url = 'http://' . $this->indexService . $this->indexServicePath . 'reindex';

        // getting the predefined classes from the config file
        $specificConfigurations = $this->privateConfig->fulltextsearch->specificconfigurations->toArray();

        if ($key = ElasticsearchUtils::recursiveArraySearch($indexname, $specificConfigurations)){
            $classes = $specificConfigurations[$key]['classes'];
        } else {
            $classes = $this->privateConfig->fulltextsearch->classes->toArray();
        }

        if (!is_array($classes)) {
            $tmp = $classes;
            $classes = explode(" ", $tmp);;
        }

        $headers = array('Content-Type' => 'application/json');
        $data = array('index' => $indexname, 'classes' => $classes) ;
        $response = Requests::post($url, $headers, json_encode($data));
        return $response;
    }


    public function triggerFullreindex($config)
    {
        $helper = new ElasticsearchHelper($config);
        $indices = $helper->getAvailableIndices();
        $responseObjects = null;

        foreach ($indices as $index) {
            // unescape the indexname and trigger reindex
            $response = $this->triggerReindexClass(str_replace("_", "/", $index));
            if (!$response->success) {
                $responseObjects[] = $response->body;
            }
        }

        return $responseObjects;


//        $this->triggerReindexClass();
    }
}
