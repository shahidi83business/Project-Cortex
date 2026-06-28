<?php

class KnowledgeObject
{
    public string $id;

    public string $artifactId;

    public string $type;

    public string $section;

    public string $content;

    public array $tags = [];

    public array $relations = [];
}