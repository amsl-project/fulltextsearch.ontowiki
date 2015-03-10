<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require_once realpath(dirname(__FILE__)) . '/classes/ElasticsearchHelper.php';
require_once realpath(dirname(__FILE__)) . '/classes/ElasticsearchUtils.php';
require_once realpath(dirname(__FILE__)) . '/classes/IndexServiceConnector.php';

/**
 * Fulltextsearch component controller.
 *
 * @category   OntoWiki
 * @package    Extensions_Fulltextsearch
 * @author     Sebastian Nuck
 * @copyright  Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class FulltextsearchController extends OntoWiki_Controller_Component
{

    public function fulltextsearchAction()
    {

        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $translate = $this->_owApp->translate;
        $searchText = null;

        if ($this->_request->query !== null) {
            $searchText = htmlspecialchars(trim($this->_request->query));
        }

        $error = false;
        $errorMsg = '';

        // check if search already contains an error
        if (!$error) {
            $esHelper = new ElasticsearchHelper($this->_privateConfig);

            // $esHelper = new ElasticsearchHelperOld();
            $result = $esHelper->search($searchText);
        }

        // if error occured set output for error page
        if ($error) {

            $this->view->errorMsg = $errorMsg;
        }

        $this->_response->setBody(json_encode($result));
    }

    /**
     *
     */
    public function searchAction()
    {
        OntoWiki::getInstance()->logger->info('searchAction');
        $translate = $this->_owApp->translate;

        $this->view->placeholder('main.window.title')->set($translate->_('Fulltext Search'));
        $this->addModuleContext('main.window.fulltextsearch.search');

        $params = $this->_request->getParams();
        $input = $params['input'];
        $from = $params['from'];

        $indices = '';
        if (isset($params['indices'])) {
            $indices = $params['indices'];
        }
        $this->view->input = $input;

        $esHelper = new ElasticsearchHelper($this->_privateConfig);

        $result = $esHelper->searchAndReturnEverything($input, $indices, $from);
        $this->view->jsonResult = $result['resultSet'];

//        $this->view->availableIndices = $esHelper->getAvailableIndices();
        $this->view->availableIndices = $esHelper->getSearchableIndices();

        // transform comma separated indices to array
        $this->view->selectedIndices = str_getcsv($indices);

        $this->view->resultArray = ElasticsearchUtils::extractResults($result['resultSet']);
        $this->view->query = $result['query'];
        $this->view->from = $from;
        $this->view->input = $input;
        $this->view->hits = $result['resultSet']['hits']['total'];

        OntoWiki::getInstance()->getNavigation()->disableNavigation();
    }

    /**
     * Displays an information page.
     * @return [type]
     */
    public function infoAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;
        $translate = $this->_owApp->translate;
        $this->view->placeholder('main.window.title')->set($translate->_('Fulltext Search Info'));
        $this->addModuleContext('main.window.fulltextsearch.info');
        $_owApp->getNavigation()->disableNavigation();

        $models = $this->getIndexableModels();

        $esHelper = new ElasticsearchHelper($this->_privateConfig);
        $indices = $esHelper->getAvailableIndicesWithMetadata();
//        $this->view->indices = $models;



//        $this->view->models = $models;
    }

    public function availableindicesAction() {
        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $models = $this->getIndexableModels();
        $this->_response->setBody(json_encode($models));
    }

    /**
     * Creates a new index, requires indexname parameter.
     * @return [type]
     */
    public function createindexAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;

        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        $indexname = $params['indexname'];

        $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
        $response = $indexServiceConnector->triggerCreateIndex($indexname);
        $indexServiceConnector->finish();
        $_owApp->logger->debug('Fulltextsearch createindexAction response:' . $response);
        // $this->_response->setHeader('Content-Type', 'text/html');
        $this->_response->setBody(json_encode($response));
    }

    /**
     * Provides basic metadata to an index like number of documents per class
     */
    public function countobjectsAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;

        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        $indexname = $params['indexname'];

        $esHelper = new ElasticsearchHelper($this->_privateConfig);
        $metadata = array();


        $specificConfigurations = $this->_privateConfig->fulltextsearch->specificconfigurations->toArray();

        if ($key = ElasticsearchUtils::recursiveArraySearch($indexname, $specificConfigurations)){
            $classes = $specificConfigurations[$key]['classes'];
        } else {
            $classes = $this->_privateConfig->fulltextsearch->classes->toArray();
        }

        if (is_array($classes)) {
            foreach ($classes as $class) {
                $count = $esHelper->countObjects($indexname, $class);
                $metadata[$class][] = $count;
            }
        } else {
            $count = $esHelper->countObjects($indexname, $classes);
            $metadata[$classes][] = $count;
        }

        $this->_response->setBody(json_encode($metadata));
    }

    public function deleteindexAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;

        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        $indexname = $params['indexname'];

        $_owApp->logger->debug('Fulltextsearch deleteindexAction: deleting index: ' . $indexname);
        $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
        $response = $indexServiceConnector->triggerDeleteIndex($indexname);
        $indexServiceConnector->finish();
        $this->_response->setHeader('Content-Type', 'text/html');
        $this->_response->setBody($response);
    }

    public function reindexAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->logger;

        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        if (isset($params['indexname'])) {
            $indexname = $params['indexname'];
        } else {
            $indexname = null;
        }

        // if index specified
        if ($indexname !== null) {

            $logger->debug('reindex action: ' . $indexname);

            $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
            $response = $indexServiceConnector->triggerReindexClass($indexname);
            $indexServiceConnector->finish();
            $this->_response->setBody($response->body);
        } else {
          // if no index specified --> full reindex
            $logger->debug('full reindex');

            $indexServiceConnector = new IndexServiceConnector($this->_privateConfig);
            $response = $indexServiceConnector->triggerFullreindex($this->_privateConfig);
            $indexServiceConnector->finish();
            $this->_response->setBody($response);
        }
    }

    /**
     * @return array
     * @throws Erfurt_Exception
     * @throws Erfurt_Store_Exception
     * @throws Exception
     */
    public function getIndexableModels()
    {
        $allModels = Erfurt_App::getInstance()->getStore()->getAvailableModels($withHidden = true);
        $ignoredModels = $this->_privateConfig->fulltextsearch->ignoredModels->toArray();

        foreach ($ignoredModels as $model) {
            if (array_key_exists($model, $allModels)) {
                unset($allModels[$model]);
            }
        }
        return $allModels;
    }
}
