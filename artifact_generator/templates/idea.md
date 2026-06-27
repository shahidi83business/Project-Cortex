<?php

class ArtifactTemplate
{
    private string $templateDir;

    public function __construct(string $templateDir = 'templates')
    {
        $this->templateDir = $templateDir;
    }

    public function create(string $type, array $data): string
    {
        $templateFile = "{$this->templateDir}/{$type}.md";

        if (!file_exists($templateFile)) {
            throw new Exception("Template '{$type}' not found.");
        }

        $template = file_get_contents($templateFile);

        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $value = implode(", ", $value);
            }

            $template = str_replace(
                "{{{$key}}}",
                $value,
                $template
            );
        }

        // Placeholderهایی که مقدار ندارند حذف شوند
        $template = preg_replace('/{{.*?}}/', '', $template);

        return $template;
    }
}