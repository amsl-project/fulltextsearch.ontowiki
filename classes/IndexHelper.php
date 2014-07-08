<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class IndexHelper
{
    
    private $privateConfig;
    private $curl;
    
    public function IndexHelper($privateConfig) {
        init();
    }
    
    public function init($privateConfig) {
        $this->privateConfig = $privateConfig;
        $this->curl = curl_init();
    }
    
    public function finish() {
        curl_close($this->curl);
        
    }
    
    public function triggerReindex($resourceUri) {
        curl_setopt($this->curl, CURLOPT_URL, $this->_privateConfig->fulltextsearch->indexService . '/erm/index/uri?resourceUri=http://erm-hd/Kontakt/Bjoern');
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_exec($this->curl);
    }
}
