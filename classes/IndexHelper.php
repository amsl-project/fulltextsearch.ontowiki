<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class IndexHelper
{
    
    private $indexService;
    private $indexServicePath;
    private $curl;
    
    public function IndexHelper($privateConfig) {
        $this->privateConfig = $privateConfig;
        $this->init();
        $this->indexService = $privateConfig->fulltextsearch->indexService;
        $this->indexServicePath = $privateConfig->fulltextsearch->indexServicePath;
    }
    
    public function init() {
        $this->curl = curl_init();
    }
    
    public function finish() {
        curl_close($this->curl);
    }
    
    public function triggerReindex($resourceUri) {
        $url = $this->indexService . $this->indexServicePath . 'uri?resourceUri=' . $resourceUri;
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        curl_exec($this->curl);
    }
    
    // public function triggerClassReindex($value = '') {
    //     curl_setopt($this->curl, CURLOPT_URL, $this->indexService . "172.18.113.206:8080/erm/query/ixuZmgSVRmDdNkUmkAIrCREjr/index/objectType=bibrm%3AContact&index=bibrm%3AContact&resourceClazz=http%3A%2F%2Fvocab.ub.uni-leipzig.de%2Fbibrm%2FContact");
    //     curl_setopt($this->curl, CURLOPT_HEADER, 0);
    //     $result = curl_exec($this->curl);
    //     if ($result === FALSE) {
    //         die(curl_error($this->curl));
    //     }
    // }
    
}
