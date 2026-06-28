<?php
include "KnowledgeConsumer.php";

class AnythingLLMConsumer extends AbstractConsumer
{
    public function sync(KnowledgePackage $package): void
    {
        $this->log("Sync {$package->artifactId}");

        // TODO:
    }

    public function delete(string $artifactId): void
    {
        $this->log("Delete {$artifactId}");

        // TODO:
    }
}