<?php

require_once 'Artifact.php';
require_once 'KnowledgeObject.php';
require_once 'KnowledgePackage.php';

class KnowledgeCompiler
{
    public function compile(Artifact $artifact): KnowledgePackage
    {
        $package = new KnowledgePackage();

        $package->artifactId = $artifact->id;

        foreach ($artifact->sections as $section => $content) {

            if (trim($content) === '') {
                continue;
            }

            $object = new KnowledgeObject();

            $object->id =
                $artifact->id .
                ':' .
                strtolower($section);

            $object->artifactId = $artifact->id;

            $object->type = $artifact->type;

            $object->section = $section;

            $object->content = trim($content);

            $object->tags =
                $artifact->metadata['tags']
                ?? [];

            $package->objects[] = $object;
        }

        return $package;
    }
}