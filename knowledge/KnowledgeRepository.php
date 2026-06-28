<?php

interface KnowledgeRepository
{
    public function save(KnowledgePackage $package): void;

    public function load(string $artifactId): ?KnowledgePackage;

    /**
     * @return KnowledgePackage[]
     */
    public function all(): array;

    public function delete(string $artifactId): void;

    public function exists(string $artifactId): bool;
}