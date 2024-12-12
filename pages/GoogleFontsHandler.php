<?php
namespace APP\plugins\generic\googleFonts\pages;

use APP\core\Application;
use APP\core\Request;
use APP\file\PublicFileManager;
use APP\handler\Handler;
use APP\plugins\generic\googleFonts\classes\GoogleFont;
use APP\plugins\generic\googleFonts\exceptions\GoogleFontsPluginException;
use APP\plugins\generic\googleFonts\GoogleFontsPlugin;
use APP\template\TemplateManager;
use Exception;
use PKP\context\Context;
use PKP\core\Core;
use PKP\plugins\PluginRegistry;

/**
 * Page handler for Google Fonts plugin settings
 *
 * Handles add and remove requests from the plugin
 * settings page.
 */
class GoogleFontsHandler extends Handler
{
    public function __construct(public GoogleFontsPlugin $plugin)
    {
        return parent::__construct();
    }

    /**
     * Add a font
     *
     * Download the font files from Google and add the
     * font to the list of available fonts.
     */
    public function add(array $args, Request $request): void
    {
        if (!$request->checkCSRF()) {
            $this->sendRedirect($request);
        }

        $request->getUserVars();
        $font = $request->getUserVar('font');

        if (!ctype_alnum($font)) {
            $this->sendRedirect($request);
        }

        $fonts = $this->plugin->loadJsonFile($this->plugin::FONTS_FILE);
        $fontDetails = $this->plugin->getFont($font, $fonts);

        if (!$fontDetails) {
            $this->sendRedirect($request);
        }

        try {
            $this->download($font);
        } catch (Exception $e) {
            $this->sendRedirect($request);
        }

        $contextId = $request->getContext()
            ? $request->getContext()->getId()
            : Application::CONTEXT_ID_NONE;

        $fonts = collect($this->plugin->getSetting($contextId, $this->plugin::FONTS_SETTING) ?? [])
            ->push(
                new GoogleFont(
                    id: $fontDetails->id,
                    family: $fontDetails->family,
                    category: $fontDetails->category,
                    subsets: $fontDetails->subsets,
                    variants: $fontDetails->variants,
                    lastModified: $fontDetails->lastModified,
                    version: $fontDetails->version,
                )
            )
            ->unique('id')
            ->sortBy('family');

        $this->plugin->updateSetting($contextId, $this->plugin::FONTS_SETTING, $fonts->values()->all());

        $this->sendRedirect($request);
    }

    /**
     * Remove a font
     *
     * Remove the font from the list of enabled fonts and
     * delete all font files from storage.
     */
    public function remove(array $args, Request $request): void
    {
        if (!$request->checkCSRF()) {
            $this->sendRedirect($request);
        }

        $request->getUserVars();
        $font = $request->getUserVar('font');

        if (!ctype_alnum($font)) {
            $this->sendRedirect($request);
        }

        $fonts = $this->plugin->loadJsonFile($this->plugin::FONTS_FILE);

        if (!$this->fontExists($font, $fonts)) {
            $this->sendRedirect($request);
        }

        $contextId = $request->getContext()
            ? $request->getContext()->getId()
            : Application::CONTEXT_ID_NONE;

        $enabled = collect($this->plugin->getSetting($contextId, $this->plugin::FONTS_SETTING) ?? [])
            ->where('id', '!=', $font);

        $this->plugin->updateSetting($contextId, $this->plugin::FONTS_SETTING, $enabled->values()->all());

        $this->delete($font);

        $this->sendRedirect($request);
    }

    /**
     * Show the help page
     */
    public function help(array $args, Request $request): void
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->display($this->plugin->getTemplateResource('help.tpl'));
    }

    /**
     * Redirect back to the settings page
     */
    protected function sendRedirect(Request $request): void
    {
        if ($request->getContext()) {
            $request->redirect(
                null,
                'management',
                'settings',
                ['website'],
                null,
                'appearance/google-fonts'
            );
        } else {
            $request->redirect(
                null,
                'admin',
                'settings',
                null,
                null,
                'appearance/google-fonts'
            );
        }
        die();
    }

    /**
     * Check if a font id matches a known font in the
     * fonts list
     */
    protected function fontExists(string $id, array $fonts): bool
    {
        foreach ($fonts as $font) {
            if ($font->id === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Download the files for a font
     *
     * @throws Exception
     */
    protected function download(string $font): void
    {
        $urls = $this->plugin->loadJsonFile("fonts/{$font}/urls.json");
        $context = Application::get()->getRequest()->getContext();
        $publicFileManager = new PublicFileManager();

        $fontDir = $this->getPublicFontFileDir($font, $publicFileManager, $context);

        $publicFileManager->rmtree($fontDir);
        $publicFileManager->mkdirtree($fontDir);

        $client = Application::get()->getHttpClient();

        foreach ($urls as $url) {
            preg_match('/\/[^\/]*.woff2/i', $url, $matches);
            if (!$matches || count($matches) > 1 || gettype($matches[0]) !== 'string') {
                throw new GoogleFontsPluginException($this->plugin, "Failed to get filename from URL `{$url}`");
            }
            $filename = str_replace('/', '', $matches[0]);
            $dest = join('/', [
                Core::getBaseDir(),
                $fontDir,
                $filename
            ]);
            $response = $client->request('GET', $url, ['sink' => $dest]);
            if ($response->getStatusCode() !== 200) {
                throw new GoogleFontsPluginException($this->plugin, "HTTP request to {$url} failed with status {$response->getStatusCode()} when fetching font {$font}.");
            }
        }
    }

    /**
     * Delete a font's downloaded files
     */
    protected function delete(string $font): void
    {
        $context = Application::get()->getRequest()->getContext();
        $publicFileManager = new PublicFileManager();
        $fontDir = $this->getPublicFontFileDir($font, $publicFileManager, $context);
        $publicFileManager->rmtree($fontDir);
    }

    /**
     * Get the directory of a font's files relative
     * to the public files directory
     */
    protected function getPublicFontFileDir(string $font, PublicFileManager $publicFileManager, ?Context $context): string
    {
        $publicFilesDir = $context
            ? $publicFileManager->getContextFilesPath($context->getId())
            : $publicFileManager->getSiteFilesPath();

        return join('/', [
            $publicFilesDir,
            $this->plugin::FONTS_PUBLIC_FILE_DIR,
            $font,
        ]);
    }
}
