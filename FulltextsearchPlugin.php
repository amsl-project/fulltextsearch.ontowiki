<?php
/**
* This file is part of the {@link http://ontowiki.net OntoWiki} project.
*
* @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
* @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
*/
require_once 'OntoWiki/Plugin.php';
require_once realpath(dirname(__FILE__)) . '/classes/IndexHelper.php';
class FulltextsearchPlugin extends OntoWiki_Plugin {
        
        public function onIndexAction($event) {
            $resource = $event->resource;
            $model = $event->model;
            $indexHelper = new IndexHelper($this->_privateConfig);
            OntoWiki::getInstance()->logger->info('[DOGE9]' . 'config: ' . print_r($this->_privateConfig->indexService, true));
        }
        public function onReindexAction($event) {
            $resource = $event->resource;
            $model = $event->model;
        }
}