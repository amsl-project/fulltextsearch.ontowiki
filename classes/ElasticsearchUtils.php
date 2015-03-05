<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class ElasticsearchUtils
{
    
    public static function extractResults($fullResults) {
        $extractedResults = array();
        
        foreach ($fullResults['hits']['hits'] as $hit) {
            $extract = array();
            $extract['id'] = $hit['_source']['@id'];
            if (isset($hit['_source']['http://purl.org/dc/elements/1.1/title'])) {
                $extract['title'] = $hit['_source']['http://purl.org/dc/elements/1.1/title'];
            } elseif (isset($hit['_source']['http://www.w3.org/2000/01/rdf-schema#label'])) {
                $extract['title'] = $hit['_source']['http://www.w3.org/2000/01/rdf-schema#label'];
            } else {
                $extract['title'] = $hit['_source']['@id'];
            }

            $extract['highlight'] = $hit['highlight'];
            $extract['index'] = $hit['_index'];
            $extract['type'] = $hit['_type'];
            array_push($extractedResults, $extract);
        }

        OntoWiki::getInstance()->logger->info('esutils: ' . print_r($extractedResults, true));
        return $extractedResults;
    }

    /**
     * Extends array_search functions and returns the key of the haystack
     * where the needle was found
     * @param $needle
     * @param $haystack
     * @return string $key
     */
    public static function recursiveArraySearch($needle, $haystack)
    {
        foreach($haystack as $key=>$value) {
            $current_key=$key;
            if($needle===$value ||
                (is_array($value) && self::recursiveArraySearch($needle,$value) !== false)) {
                return $current_key;
            }
        }
        return false;
    }
}
