<?php
include "JsonKnowledgeIndex.php";

interface KnowledgeIndex
{
    public function index(KnowledgePackage $package): void;

    public function remove(string $artifactId): void;

    public function findByType(string $type): array;

    public function findByTag(string $tag): array;

    public function findByRelation(string $relation): array;
}