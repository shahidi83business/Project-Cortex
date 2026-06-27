<?php

class ConversationParser
{
    /**
     * Parse an OpenAI conversation export.
     *
     * @param string|array $input JSON file path or decoded array
     */
    public function parse(string|array $input): array
    {
        $conversations = is_string($input)
            ? json_decode(file_get_contents($input), true)
            : $input;

        $normalized = [];

        foreach ($conversations as $conversation) {
            $normalized[] = $this->normalizeConversation($conversation);
        }

        return array_map([$this, 'buildTurns'], $normalized);
    }

    /**
     * Convert OpenAI format to normalized conversation.
     */
    private function normalizeConversation(array $conversation): array
    {
        $messages = [];

        foreach ($conversation['mapping'] as $node) {

            if (!isset($node['message'])) {
                continue;
            }

            $message = $node['message'];

            if (!isset($message['author']['role'])) {
                continue;
            }

            $role = $message['author']['role'];

            if (!in_array($role, ['user', 'assistant'])) {
                continue;
            }

            if (($message['content']['content_type'] ?? '') !== 'text') {
                continue;
            }

            $content = implode("\n", $message['content']['parts'] ?? []);

            if (trim($content) === '') {
                continue;
            }

            $messages[] = [
                'id' => $message['id'],
                'role' => $role,
                'created_at' => $message['create_time'],
                'content' => $content,
            ];
        }

        usort($messages, function ($a, $b) {
            return ($a['created_at'] ?? 0) <=> ($b['created_at'] ?? 0);
        });

        return [
            'id' => $conversation['id'],
            'title' => $conversation['title'],
            'created_at' => $conversation['create_time'],
            'updated_at' => $conversation['update_time'],
            'messages' => $messages,
        ];
    }

    /**
     * Convert messages into turns.
     */
    private function buildTurns(array $conversation): array
    {
        $turns = [];
        $currentTurn = null;
        $turnId = 1;

        foreach ($conversation['messages'] as $message) {

            if ($message['role'] === 'user') {

                if (
                    $currentTurn !== null &&
                    $currentTurn['assistant'] === null
                ) {

                    $currentTurn['user']['content'] .= "\n\n" . $message['content'];

                } else {

                    if ($currentTurn !== null) {
                        $turns[] = $currentTurn;
                    }

                    $currentTurn = [
                        'id' => $turnId++,
                        'user' => [
                            'id' => $message['id'],
                            'created_at' => $message['created_at'],
                            'content' => $message['content'],
                        ],
                        'assistant' => null,
                    ];
                }

            } elseif ($message['role'] === 'assistant') {

                if ($currentTurn === null) {
                    continue;
                }

                if ($currentTurn['assistant']) {

                    $currentTurn['assistant']['content'] .= "\n\n" . $message['content'];

                } else {

                    $currentTurn['assistant'] = [
                        'id' => $message['id'],
                        'created_at' => $message['created_at'],
                        'content' => $message['content'],
                    ];
                }
            }
        }

        if ($currentTurn !== null) {
            $turns[] = $currentTurn;
        }

        return [
            'id' => $conversation['id'],
            'title' => $conversation['title'],
            'created_at' => $conversation['created_at'],
            'updated_at' => $conversation['updated_at'],
            'turns' => $turns,
        ];
    }
}