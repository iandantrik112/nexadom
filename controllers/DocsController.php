<?php
declare(strict_types=1);
namespace App\Controllers;
use App\System\NexaController;
/**
 * DocsController - Menampilkan dokumentasi NexaUI (10 topik)
 * Route: /docs, /docs/{topic}
 */
class DocsController extends NexaController
{
    private const DOCS_MAP = [
        'getting-started' => '01-getting-started.md',
        'tutorial' => '02-tutorial.md',
        'general-topics' => '03-general-topics.md',
        'libraries' => '04-libraries.md',
        'helpers' => '05-helpers.md',
        'database' => '06-database.md',
        'template-engine' => '07-template-engine.md',
        'components' => '08-components.md',
        'advanced' => '09-advanced.md',
        'api' => '11-api.md',
        'dashboard' => '12-dashboard.md',
        'appendices' => '10-appendices.md',
    ];

    private const DOCS_TITLES = [
        'getting-started' => '1. Getting Started',
        'tutorial' => '2. Tutorial',
        'general-topics' => '3. General Topics',
        'libraries' => '4. Libraries Reference',
        'helpers' => '5. Helpers Reference',
        'database' => '6. Database Reference',
        'template-engine' => '7. Template Engine (NexaDom)',
        'components' => '8. Components & UI',
        'advanced' => '9. Advanced Topics',
        'api' => '10. API Reference',
        'dashboard' => '11. Dashboard',
        'appendices' => '12. Appendices',
    ];

    /**
     * Daftar dokumentasi
     */
    public function index(array $params = []): void
    {
        $this->assignVars([
            'page_title' => 'NexaUI Documentation',
            'docs_list' => self::DOCS_TITLES,
            'base_url' => $this->url('/'),
        ]);
        $this->divert();
        $this->render('docs/index');
    }

    /**
     * Konten topik tertentu
     * Route /docs/{params} → injectParameters mengirim string ke param $params
     */
    public function topic(array|string $params = []): void
    {
        $topic = is_array($params) ? ($params['topic'] ?? $params['params'] ?? '') : (string)$params;
        $topic = is_array($topic) ? ($topic[0] ?? '') : $topic;

        if (empty($topic) || !isset(self::DOCS_MAP[$topic])) {
            $this->redirect($this->url('/home'));
            return;
        }

        $docsPath = dirname(__DIR__) . '/docs/' . self::DOCS_MAP[$topic];
        if (!file_exists($docsPath)) {
            $this->redirect($this->url('/home'));
            return;
        }

        $markdown = file_get_contents($docsPath);
        $htmlContent = $this->getMarkdown()->text($markdown);
        // Escape { } agar tidak diproses template engine (supaya {if}, {endif}, {var} tampil literal)
        $htmlContent = str_replace(['{', '}'], ['&#123;', '&#125;'], $htmlContent);

        $prevTopic = null;
        $nextTopic = null;
        $keys = array_keys(self::DOCS_MAP);
        $idx = array_search($topic, $keys);
        if ($idx !== false) {
            $prevTopic = $idx > 0 ? $keys[$idx - 1] : null;
            $nextTopic = $idx < count($keys) - 1 ? $keys[$idx + 1] : null;
        }

        $this->assignVars([
            'page_title' => self::DOCS_TITLES[$topic] . ' - NexaUI',
            'doc_title' => self::DOCS_TITLES[$topic],
            'doc_content' => $htmlContent,
            'doc_topic' => $topic,
            'prev_topic' => $prevTopic,
            'prev_title' => $prevTopic ? self::DOCS_TITLES[$prevTopic] : null,
            'next_topic' => $nextTopic,
            'next_title' => $nextTopic ? self::DOCS_TITLES[$nextTopic] : null,
            'docs_url' => $this->url('/home'),
        ]);
        $this->divert();
        $this->render('docs/content');
    }
}
