<?php

namespace HireVoice\Neo4j\Extension;

class GetNodeRelationshipsLight extends \Everyman\Neo4j\Command\GetNodeRelationships
{
    protected function handleResult($code, $headers, $data)
    {
        if ((int)($code / 100) == 2) {
            return $data;
        } else {
            $this->throwException('Unable to retrieve node relationships', $code, $headers, $data);
        }
    }
}

