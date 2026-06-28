<?php
class AnythingLLMClient
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function uploadMarkdown(
        string $filename,
        string $markdown
    ): string {

        $response =  $this->request(
            'POST',
            '/v1/document/raw-text',
            [
                'metadata'   => ['title' => $filename],
                'textContent' => $markdown
            ]
        );
        return $response['documents'][0]['location'];
    }

        public function listWorkspaces(): array
    {
        return $this->request(
            'GET',
            '/v1/workspaces'
        );
    }
    public function updateEmbeddings(
        string $workspace,
        array $adds = [],
        array $deletes = []
    ): array
    {
        return $this->request(
            'POST',
            "/v1/workspace/{$workspace}/update-embeddings",
            [
                'adds' => $adds,
                'deletes' => $deletes
            ]
        );
    }

    private function request(
        string $method,
        string $endpoint,
        array $body = []
    ): array {

        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL => $this->baseUrl . $endpoint,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_CUSTOMREQUEST => $method,

            CURLOPT_HTTPHEADER => [

                'Authorization: Bearer ' . $this->apiKey,

                'Content-Type: application/json',

                'Accept: application/json'

            ],

            CURLOPT_POSTFIELDS => json_encode($body)

        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status >= 300) {
            throw new Exception($response);
        }

        return json_decode($response, true);
    }
}