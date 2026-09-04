<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * PHP-template view renderer with layout support.
 * Templates are plain PHP files; no compilation, no cache needed.
 * The e() global escapes output — use it everywhere.
 */
class View
{
    private array $shared = [];

    public function __construct(
        private string $viewPath,
        private array  $appConfig = []
    ) {}

    /**
     * Share data with every view.
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Render a view template and return the HTML string.
     * $name uses slash notation: 'public/home', 'admin/packages/index', etc.
     */
    public function render(string $name, array $data = []): string
    {
        $file = $this->viewPath . '/' . str_replace('.', '/', $name) . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$name} ({$file})");
        }
        $data = array_merge($this->shared, $data);
        return $this->include($file, $data);
    }

    /**
     * Render a view inside a layout.
     * The layout receives $content (the rendered inner template).
     */
    public function renderWithLayout(string $layout, string $view, array $data = []): string
    {
        $content = $this->render($view, $data);
        $layoutData = array_merge($data, ['content' => $content]);
        return $this->render('layouts/' . $layout, $layoutData);
    }

    private function include(string $file, array $data): string
    {
        $view = $this;
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Render a partial. Called from within templates.
     */
    public function partial(string $name, array $data = []): string
    {
        return $this->render('partials/' . $name, array_merge($this->shared, $data));
    }
}
