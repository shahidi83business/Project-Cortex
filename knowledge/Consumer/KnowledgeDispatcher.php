<?php
include "ConsumerInterface.php";

class KnowledgeDispatcher
{
    /**
     * @var ConsumerInterface[]
     */
    private array $consumers = [];

    public function addConsumer(
        ConsumerInterface $consumer
    ): self
    {
        $this->consumers[] = $consumer;

        return $this;
    }

    public function sync(
        Artifact $artifact
    ): void
    {
        foreach ($this->consumers as $consumer) {

            try {

                $consumer->sync($artifact);

            } catch (Throwable $e) {

                error_log(sprintf(
                    '[%s] %s',
                    $consumer->name(),
                    $e->getMessage()
                ));

            }

        }
    }
}