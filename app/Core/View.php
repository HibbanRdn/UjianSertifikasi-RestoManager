<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private readonly string $basePath)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        $templatePath = $this->basePath . '/' . $template . '.php';
        $layoutPath = $this->basePath . '/' . $layout . '.php';

        if (!is_file($templatePath) || !is_file($layoutPath)) {
            throw new \RuntimeException('Template tidak ditemukan.');
        }

        $variables = array_merge($this->shared, $data);
        extract($variables, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        require $layoutPath;
    }
}
