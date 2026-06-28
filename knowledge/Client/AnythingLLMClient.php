class AnythingLLMClient
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct(
        string $baseUrl,
        string $apiKey
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');

        $this->apiKey = $apiKey;
    }

    public function upload(
        string $workspace,
        string $filename,
        string $content
    ): void
    {
        // TODO
    }

    public function syncWorkspace(
        string $workspace
    ): void
    {
        // TODO
    }

    public function delete(
        string $workspace,
        string $filename
    ): void
    {
        // TODO
    }
}