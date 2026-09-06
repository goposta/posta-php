<?php

declare(strict_types=1);

namespace Posta\Resources;

use Posta\Http;
use Posta\PostaException;

/**
 * Manages templates and the versions and localizations beneath them.
 *
 * A template is a named container. Its content lives on immutable versions,
 * and each version carries one localization per language. Sending resolves the
 * template's active version unless a caller names another.
 */
class Templates
{
    public function __construct(private Http $http)
    {
    }

    /**
     * Add a template. Content is added afterwards as a version.
     *
     * @param array{name: string, description?: string, default_language?: string, sample_data?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function create(array $request): array
    {
        return $this->http->post(Http::WS . '/templates', $request);
    }

    /**
     * Return a page of templates, without the non-active version bodies.
     *
     * @param array{page?: int, size?: int, search?: string} $options
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function list(array $options = []): array
    {
        return $this->http->getPage(Http::WS . '/templates' . Http::query($options));
    }

    /**
     * Return one template with its active version.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function get(int $id): array
    {
        return $this->http->get(Http::WS . '/templates/' . Http::seg($id));
    }

    /**
     * Change a template's metadata.
     *
     * @param array{name?: string, description?: string, default_language?: string, sample_data?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function update(int $id, array $request): array
    {
        return $this->http->put(Http::WS . '/templates/' . Http::seg($id), $request);
    }

    /**
     * Remove a template and every version under it.
     *
     * @throws PostaException
     */
    public function delete(int $id): void
    {
        $this->http->delete(Http::WS . '/templates/' . Http::seg($id));
    }

    /**
     * Render unsaved template source with the given variables.
     *
     * @param array{subject_template: string, html_template?: string, text_template?: string, stylesheet_id?: int, template_data?: array<string, mixed>} $request
     * @return array{subject: string, html: string, text: string}
     * @throws PostaException
     */
    public function preview(array $request): array
    {
        return $this->http->post(Http::WS . '/templates/preview', $request);
    }

    /**
     * Send a test rendering of a template to real inboxes.
     *
     * @param array{to: string[], from?: string, language?: string, template_data?: array<string, mixed>} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function sendTest(int $id, array $request): array
    {
        return $this->http->post(Http::WS . '/templates/' . Http::seg($id) . '/send-test', $request);
    }

    /**
     * Return a template and all its versions in portable form.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function export(int $id): array
    {
        return $this->http->get(Http::WS . '/templates/' . Http::seg($id) . '/export');
    }

    /**
     * Recreate a template from an exported payload.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function import(array $payload): array
    {
        return $this->http->post(Http::WS . '/templates/import', $payload);
    }

    /**
     * Create a template from a raw HTML document.
     *
     * @param array{name: string, html: string, description?: string, subject_template?: string, language?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function importHtml(array $request): array
    {
        return $this->http->post(Http::WS . '/templates/import-html', $request);
    }

    /**
     * Return every version of a template, newest first.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listVersions(int $templateId): array
    {
        return $this->http->get(Http::WS . '/templates/' . Http::seg($templateId) . '/versions') ?? [];
    }

    /**
     * Open a new draft version, copying the active one's localizations.
     *
     * @param array{sample_data?: string, stylesheet_id?: int} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createVersion(int $templateId, array $request = []): array
    {
        return $this->http->post(
            Http::WS . '/templates/' . Http::seg($templateId) . '/versions',
            $request
        );
    }

    /**
     * Change a version's stylesheet.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateVersion(int $templateId, int $versionId, ?int $stylesheetId): array
    {
        return $this->http->put(
            Http::WS . '/templates/' . Http::seg($templateId) . '/versions/' . Http::seg($versionId),
            ['stylesheet_id' => $stylesheetId]
        );
    }

    /**
     * Remove a version. The active version cannot be deleted.
     *
     * @throws PostaException
     */
    public function deleteVersion(int $templateId, int $versionId): void
    {
        $this->http->delete(
            Http::WS . '/templates/' . Http::seg($templateId) . '/versions/' . Http::seg($versionId)
        );
    }

    /**
     * Make a version the one that sends.
     *
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function activateVersion(int $templateId, int $versionId): array
    {
        return $this->http->post(
            Http::WS . '/templates/' . Http::seg($templateId) . '/activate/' . Http::seg($versionId)
        );
    }

    /**
     * Return every language defined on a version.
     *
     * @return array<int, array<string, mixed>>
     * @throws PostaException
     */
    public function listLocalizations(int $templateId, int $versionId): array
    {
        return $this->http->get(
            Http::WS . '/templates/' . Http::seg($templateId)
            . '/versions/' . Http::seg($versionId) . '/localizations'
        ) ?? [];
    }

    /**
     * Add a language to a version.
     *
     * @param array{language: string, subject_template: string, html_template?: string, text_template?: string, builder_json?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function createLocalization(int $templateId, int $versionId, array $request): array
    {
        return $this->http->post(
            Http::WS . '/templates/' . Http::seg($templateId)
            . '/versions/' . Http::seg($versionId) . '/localizations',
            $request
        );
    }

    /**
     * Change a language's content.
     *
     * Localizations are addressed by their own id, not by template and
     * version.
     *
     * @param array{subject_template?: string, html_template?: string, text_template?: string, builder_json?: string} $request
     * @return array<string, mixed>
     * @throws PostaException
     */
    public function updateLocalization(int $localizationId, array $request): array
    {
        return $this->http->put(
            Http::WS . '/localizations/' . Http::seg($localizationId),
            $request
        );
    }

    /**
     * Remove a language from its version.
     *
     * @throws PostaException
     */
    public function deleteLocalization(int $localizationId): void
    {
        $this->http->delete(Http::WS . '/localizations/' . Http::seg($localizationId));
    }

    /**
     * Render a saved version in one language.
     *
     * @param array<string, mixed> $templateData
     * @return array{subject: string, html: string, text: string}
     * @throws PostaException
     */
    public function previewLocalization(
        int $templateId,
        int $versionId,
        string $language,
        array $templateData = []
    ): array {
        return $this->http->post(
            Http::WS . '/templates/' . Http::seg($templateId)
            . '/versions/' . Http::seg($versionId) . '/preview',
            Http::body(['language' => $language, 'template_data' => $templateData ?: null])
        );
    }
}
