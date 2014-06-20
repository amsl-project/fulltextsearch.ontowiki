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
            $extract['title'] = $hit['_source']['http://purl.org/dc/elements/1.1/title'];
            $extract['highlight'] = $hit['highlight'];
            $extract['index'] = $hit['_index'];
            array_push($extractedResults, $extract);
        }

        OntoWiki::getInstance()->logger->info('esutils: ' . print_r($extractedResults, true));
        return $extractedResults;
    }
}
