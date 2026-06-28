<?php
class AnythingLLMConsumer extends AbstractConsumer
{
    public function __construct(
        private AnythingLLMClient $client,
        private MarkdownExporter $exporter,
        private string $workspace
    ) {}

    public function sync(KnowledgeContext $context): void
    {
        $artifact = $context->artifact;

        $markdown = $this->exporter->export($artifact);

        $this->client->uploadMarkdown(
            $artifact->id . '.md',
            $markdown
        );

        $this->client->updateEmbeddings(
            $this->workspace
        );
    }

    public function delete(string $artifactId): void
    {
        // TODO
    }
}