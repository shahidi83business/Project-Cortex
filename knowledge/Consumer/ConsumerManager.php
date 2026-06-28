<?php

class ConsumerManager
{
    /**
     * @var KnowledgeConsumer[]
     */
    private array $consumers = [];

    public function add(KnowledgeConsumer $consumer): void
    {
        $this->consumers[] = $consumer;
    }

    public function sync(KnowledgePackage $package): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->sync($package);
        }
    }

    public function delete(string $artifactId): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->delete($artifactId);
        }
    }
}