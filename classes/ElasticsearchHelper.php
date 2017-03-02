<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require realpath(dirname(__FILE__)) . '/../libraries/vendor/autoload.php';

// require 'vendor/autoload.php';

class ElasticsearchHelper
{
    
    /**
     * The component private config
     *
     * @var Zend_Config
     */
    private static $_privateConfig;
    
    private static $client = null;
    
    private static $index;
    
    /**
     * [ElasticsearchHelper description]
     */
    public function ElasticsearchHelper($privateConfig) {
        static ::$_privateConfig = $privateConfig;
    }
    
    /**
     * To prevent multiple instances of this class, we implement a singleton-like
     * pattern which makes this class static and not instantiable.
     */
    public static function getClient($config) {
        if (!isset(static ::$client)) {
            static ::init($config);
        }
        return static ::$client;
    }
    
    /**
     * Init function.
     */
    private static function init($config) {
        
        /**
         * ElasticSearch Configuration
         */
        $params = array();
        $params['hosts'] = array($config->fulltextsearch->hosts);
        static ::$client = new Elasticsearch\Client($params);

    }
    
    /**
     * Returns all available Indices except for the ones that start with a dot.
     */
    public function getAvailableIndices() {
        $logger = OntoWiki::getInstance()->logger;
        $query = '_aliases';
        $indices = array_keys($this->getClient(static ::$_privateConfig)->indices()->getAliases());
        
        // filter all indices that start with a . (e.g. .settings)
        $pattern = '/^.*/';
        foreach ($indices as $key => $index) {
            $res = preg_match($pattern, $index);
            if ($res) {
                unset($key);
            }
        }
        
        // remove settings index.
        $indices = array_diff($indices, array(".settings"));
        return $indices;
    }
    
    public function getAvailableIndicesWithMetadata() {
        $logger = OntoWiki::getInstance()->logger;
        $indices = $this->getClient(static ::$_privateConfig)->indices()->status();
        return $indices['indices'];
    }

    /**
     * Retrieves different meta data from the index like count of documents per object type
     * @param $indexname the name of the index (uri)
     *
     */
    public function countObjects($indexname, $classname) {
        $indexname = strtolower($indexname);
        if (isset($indexname) && isset($classname) && $this->indexExists(str_replace("/", "_", $indexname))){
            $query['index'] = str_replace("/", "_", $indexname);
            $query['body']['query']['filtered']['filter']['type']['value'] = str_replace("#", "//", $classname);
            $return = $this->getClient(static ::$_privateConfig)->count($query);
            $queryEncoded = json_encode($query);
            return $return['count'];
        }
        return null;
    }

    public function indexExists($indexname) {
        $indices = array_keys($this->getClient(static ::$_privateConfig)->indices()->getAliases());;
        return in_array($indexname, $indices);
    }
    
    /**
     * The search function triggered by the autocomplete function.
     * It only returns specific fields and not the whole result set.
     * @param  String $searchTerm the term the user searched for.
     * @return Array $results the array of results containing only
     * the information to be displayed on the autocomplete feature.
     */
    public function search($searchTerm) {
        
        $logger = OntoWiki::getInstance()->getCustomLogger('fulltextsearch');
        
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $titleHelper = new OntoWiki_Model_TitleHelper();
        
        $logger->debug('fulltextsearch: searching for ' . $searchTerm);

        if (isset($searchTerm)) {
            $searchTerms = explode(" ", $searchTerm);

            $partialQuery = '';
            foreach ($searchTerms as $term) {
                $partialQuery.= $term . "* ";
            }
            $searchableIndices = $this->getSearchableIndices();
            $query['index'] = $searchableIndices;

            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;

            $highlightFields = array();
            foreach ($fields as $field) {
                $field = $this->removeBoostingOperator($field);
                $tmp = array($field => array('fragment_size' => 500, 'number_of_fragments' => 1));
                array_push($highlightFields, $tmp);
            }
            $query['body']['highlight'] = array('fields' => $highlightFields);
            
            $logger->info('elasticsearch query:' . print_r(($query), true));
            $fullResults = $this->getClient(static ::$_privateConfig)->search($query);

            $logger->info('fullresult:' . print_r(($fullResults), true));
            $highlightCount = 0;
            $total = $fullResults['hits']['total'];
            foreach ($fullResults['hits']['hits'] as $hit) {
                if($hit['_type'] == 'indexsettings') {
                    continue;
                }

                $type = $titleHelper->getTitle($hit['_type']);
                if (isset($hit['highlight'])) {
                    $highlight = $hit['highlight'];
                    $highlightValues[] = array_values($highlight);
                    $highlightKeys[] = array_keys($highlight);
                    $highlightValue = $highlightValues[$highlightCount];
                    $highlightKey = $highlightKeys[$highlightCount];
                    $originIndex = str_replace("_", "/", $hit['_index']);
                    
                    // show title or label
                    $title = $hit['_source']['@id'];
                    if (isset($hit['_source']['http://purl.org/dc/elements/1.1/title'])) {
                        $title = $hit['_source']['http://purl.org/dc/elements/1.1/title'];
                    } elseif (isset($hit['_source']['http://www.w3.org/2000/01/rdf-schema#label'])) {
                        $title = $hit['_source']['http://www.w3.org/2000/01/rdf-schema#label'];
                    }
                    
                    $indexName = $titleHelper->getTitle($originIndex);
                    $highlightKey = $titleHelper->getTitle($highlightKey[0]);

                    $results[] = array('uri' => $hit['_source']['@id'],
                        'title' => $title,
                        'highlight' => $highlightValue,
                        'highlightKey' => $highlightKey,
                        'originIndex' => $indexName,
                        'type' => $type,
                        'total' => $total);
                } else {
                    $title = $hit['_source']['@id'];
                    $results[] = array('uri' => $hit['_source']['@id'], 'title' => $title, 'highlight' => '');
                }
                $highlightCount++;
            }
        }
        return $results;
    }
    
    /**
     * Returns the full result set to a given search term.
     * @param  String $searchTerm The search term.
     * @param  String $indices The indices to be searched.
     * @return array $fullResults The array containing the complete result set.
     */
    public function searchAndReturnEverything($searchTerm, $indices, $from) {
        
        $logger = OntoWiki::getInstance()->logger;
        
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $size = static ::$_privateConfig->fulltextsearch->size;
        $titleHelper = new OntoWiki_Model_TitleHelper();
        
        // if no index was specified ignore the parameter "index" to search all indices
        if ($indices !== '') {
            $query['index'] = $indices;
        } else {
            $searchableIndices = $this->getSearchableIndices();
            $query['index'] = $searchableIndices;
        }
        if (isset($searchTerm)) {
            $searchTerms = explode(" ", $searchTerm);
            
            // build wildcard query
            $partialQuery = '';
            foreach ($searchTerms as $term) {
                $partialQuery.= $term . "* ";
            }

            $size = 10000;
            $query['body']['size'] = $size;
            $query['body']['from'] = $from;
            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;
            
            $highlightFields = array();
            foreach ($fields as $field) {
                $field = $this->removeBoostingOperator($field);
                $tmp = array($field => array('fragment_size' => 500, 'number_of_fragments' => 1));
                array_push($highlightFields, $tmp);
            }
            $query['body']['highlight'] = array('fields' => $highlightFields);
            
            $resultSet = $this->getClient(static ::$_privateConfig)->search($query);
            
            // if no results via wildcard search have been found,
            // a new fuzzy search is triggered
            if ($resultSet['hits']['total'] == 0) {
                $partialQuery = '';
                foreach ($searchTerms as $term) {
                    if ((strcmp($term, 'OR') !== 0)
                        && (strcmp($term, 'AND') !== 0)
                        && (strcmp($term, 'NOT') !== 0)
                        && (strpos($term, '(') !== 'FALSE')
                        && (strpos($term, ')') !== 'FALSE')) {
                        $partialQuery.= $term . "~ ";
                    } else {
                        $partialQuery.= $term . " ";
                    }
                }
                $query['body']['query']['query_string']['query'] = $partialQuery;
                $resultSet = $this->getClient(static ::$_privateConfig)->search($query);
            }
            
            $result = array();
            $result['resultSet'] = $resultSet;
            $result['query'] = $query;
            
            return $result;
        }
        return null;
    }
    
    /**
     * clone
     *
     * Since this a singleton, cloning is not allowed.
     */
    protected function __clone() {
    }
    
    /**
     * [removeBoostingOperator description]
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    private function removeBoostingOperator($field) {
        $pos = strpos($field, "^");
        if ($pos === false) {
            return $field;
        } else {
            return substr($field, 0, $pos);
        }
    }

    /**
     * @return array
     * @throws Erfurt_Exception
     * @throws Erfurt_Store_Exception
     * @throws Exception
     */
    public function getSearchableIndices()
    {
        $_erfurt = Erfurt_App::getInstance();

        // get all accessible models for the current user
        $models = $_erfurt->getStore()->getAvailableModels($withHidden = true);

        // get those models, that shall only be search if currently selected by the user
        $directAccessModels = static ::$_privateConfig->fulltextsearch->directAccessModels->toArray();

        // get all available indices
        $availableIndices = $this->getAvailableIndices();

        $selectedModel = OntoWiki::getInstance()->selectedModel->getModelUri();

        // make sure thath we don't have selected a model whose index
        // should only be searched directly
        if (!in_array($selectedModel, $directAccessModels)) {
            // remove not accessible indices from list of available indices
            $indexnames = array();
            foreach ($models as $model) {
                $indexname = $model['modelIri'];
                $escapedIndexname = strtolower(str_replace("/", "_", $model['modelIri']));

                // if we the index belongs to a readable model
                // and is not an index that must be directly accessed
                if (in_array($escapedIndexname, $availableIndices) && !in_array($indexname, $directAccessModels)) {
//            if (in_array($escapedIndexname, $availableIndices)){
                    $indexnames[] = $escapedIndexname;
                }
            }
        } else {
            // if the selected model is a direct access model, return only the corresponding index
            $indexnames[] = strtolower(str_replace("/", "_", $selectedModel));
        }

        return $indexnames;
    }
}
