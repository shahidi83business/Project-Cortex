<?php
include "ConsumerInterface.php";

class AnythingLLMConsumer implements ConsumerInterface
{
    public function __construct(
        private AnythingLLMClient $client,
        private MarkdownExporter $exporter,
        private string $workspace
    ) {}

    public function name(): string
    {
        return 'AnythingLLM';
    }
    
    public function sync(KnowledgeContext $context): void
    {
        $artifact = $context->artifact;

        $markdown = $this->exporter->export($artifact);

        $location = $this->client->uploadMarkdown(
            $artifact->id . '.md',
            $markdown
        );

        $this->client->updateEmbeddings(
            $this->workspace,
            [$location]
        );
    }
    public function delete(string $artifactId): void
    {
        // TODO
    }
}