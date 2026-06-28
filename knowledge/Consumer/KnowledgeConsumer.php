<?php

interface KnowledgeConsumer
{
    public function sync(KnowledgePackage $package): void;

    public function delete(string $artifactId): void;
}