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
    private $curl;
    
    public function IndexHelper($privateConfig) {
        $this->privateConfig = $privateConfig;
        $this->init();
        $this->indexService = $privateConfig->fulltextsearch->indexService;
    }
    
    public function init() {
        $this->curl = curl_init();
    }
    
    public function finish() {
        curl_close($this->curl);
    }
    
    public function triggerReindex($resourceUri) {
        curl_setopt($this->curl, CURLOPT_URL, $this->indexService . '/erm/index/uri?resourceUri=' . $resourceUri);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        $result = curl_exec($this->curl);
        if ($result === FALSE) {
            die(curl_error($this->curl));
        }
    }
}
