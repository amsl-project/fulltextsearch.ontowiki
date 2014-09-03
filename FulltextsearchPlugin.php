<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
require_once 'OntoWiki/Plugin.php';
require_once realpath(dirname(__FILE__)) . '/classes/IndexServiceConnector.php';
class FulltextsearchPlugin extends OntoWiki_Plugin
{
    
    public function onIndexAction($event) {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;
        $resource = $event->resource;
        $model = $event->model;
        $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
        $class = $this->findClass($event->resource);
        $return = $indexServiceConnector->triggerReindex($event->resource, $class);
        $indexServiceConnector->finish();
        $logger->debug('onIndexAction: ' . print_r($return, true));
    }
    
    public function onDeleteResourceAction($event) {
        $model = $event->model;
        $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
        $resources = $event->resources;
        if (!is_array($resources)) {
            $resources = array($resources);
        }
        foreach ($resources as $resource) {
            if ($this->canBeDeleted($resource)) {
                OntoWiki::getInstance()->logger->info('FulltextsearchPlugin: resource ' . $resource . ' can be deleted');
                $indexServiceConnector->triggerDeleteResource($resource);
            } else {
                OntoWiki::getInstance()->logger->info('FulltextsearchPlugin: resource ' . $resource . ' cannot be deleted');
                $indexServiceConnector->triggerReindex($resource);
            }
        }
        $indexServiceConnector->finish();
    }

    public function onFullreindexAction($event) {
        $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
        $return = $indexServiceConnector->triggerFullreindex();
        $indexServiceConnector->finish();
    }
    
    /**
     * Checks whether the resource still exists in another knowledge base.
     * @param  $resource
     * @return type of $resource
     */
    private function canBeDeleted($resource) {
        $_owApp = OntoWiki::getInstance();
        $store = $_owApp->erfurt->getStore();
        $selectedModel = $_owApp->selectedModel;
        $modelResource = new OntoWiki_Model_Resource($store, $selectedModel, (string)$resource);
        $predicates = $modelResource->getPredicates();
        if (count($predicates) == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Find a class to a given resource uri.
     */
    public function findClass($resource) {
        $_owApp = OntoWiki::getInstance();
        $store = $_owApp->erfurt->getStore();
        $selectedModel = $_owApp->selectedModel;
        $modelResource = $selectedModel->getResource($resource);
        $description = $modelResource->getDescription();
        $type = $description[$resource][EF_RDF_TYPE][0]['value'];
        if ($type !== null) {
            return $type;
        } else {
            return null;
        }
    }
}
