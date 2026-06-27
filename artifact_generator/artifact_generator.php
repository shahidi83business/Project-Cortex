<?php

class ArtifactGenerator
{
    public function generate(array $conversation, string $type): string
    {
        $method = 'generate' . ucfirst($type);

        if (!method_exists($this, $method)) {
            throw new Exception("Unknown artifact type: {$type}");
        }

        return $this->$method($conversation);
    }

    private function generateDecision(array $conversation): string
    {
        return $this->render('decision', [
            'id' => $this->generateId('DEC'),
            'conversation' => $conversation['id'],
            'segment' => '1',
            'created_at' => date('Y-m-d'),
            'title' => '',
            'context' => $this->conversationToMarkdown($conversation),
            'decision' => '',
            'rationale' => '',
            'consequences' => '',
            'references' => ''
        ]);
    }

    private function generateResearch(array $conversation): string
    {
        return $this->render('research', [
            'id' => $this->generateId('RES'),
            'conversation' => $conversation['id'],
            'segment' => '1',
            'created_at' => date('Y-m-d'),
            'title' => '',
            'objective' => '',
            'background' => $this->conversationToMarkdown($conversation),
            'findings' => '',
            'evidence' => '',
            'conclusion' => '',
            'references' => ''
        ]);
    }

    private function generateIdea(array $conversation): string
    {
        return $this->render('idea', [
            'id' => $this->generateId('IDE'),
            'conversation' => $conversation['id'],
            'segment' => '1',
            'created_at' => date('Y-m-d'),
            'title' => '',
            'description' => $this->conversationToMarkdown($conversation),
            'motivation' => '',
            'benefits' => '',
            'drawbacks' => '',
            'next_steps' => '',
            'references' => ''
        ]);
    }

    private function generateQuestion(array $conversation): string
    {
        return $this->render('question', [
            'id' => $this->generateId('QUE'),
            'conversation' => $conversation['id'],
            'segment' => '1',
            'created_at' => date('Y-m-d'),
            'title' => '',
            'question' => '',
            'context' => $this->conversationToMarkdown($conversation),
            'assumptions' => '',
            'answer' => '',
            'references' => ''
        ]);
    }

    private function render(string $template, array $data): string
    {
        $content = file_get_contents("templates/{$template}.md");

        foreach ($data as $key => $value) {
            $content = str_replace(
                "{{{$key}}}",
                $value,
                $content
            );
        }

        return preg_replace('/{{.*?}}/', '', $content);
    }

    private function conversationToMarkdown(array $conversation): string
    {
        $text = '';

        foreach ($conversation['turns'] as $turn) {

            $text .= "### User\n";
            $text .= trim($turn['user']['content']) . "\n\n";

            if ($turn['assistant']) {
                $text .= "### Assistant\n";
                $text .= trim($turn['assistant']['content']) . "\n\n";
            }
        }

        return trim($text);
    }

    private function generateId(string $prefix): string
    {
        return sprintf(
            '%s-%04d',
            $prefix,
            random_int(1, 9999)
        );
    }
}