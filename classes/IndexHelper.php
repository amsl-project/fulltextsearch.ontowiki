<?php
/**
* This file is part of the {@link http://ontowiki.net OntoWiki} project.
*
* @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
* @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
*/
class IndexHelper {

    private $privateConfig;

    public function IndexHelper($privateConfig) {
        $this->privateConfig = $privateConfig;
        // OntoWiki::getInstance()->logger->info('[DOGE7b]: ' . print_r($this->_privateConfig, true));
    }
}