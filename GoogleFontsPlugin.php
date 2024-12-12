<?php

namespace APP\plugins\generic\googleFonts;

use APP\core\Application;
use APP\file\PublicFileManager;
use APP\plugins\generic\googleFonts\pages\GoogleFontsHandler;
use APP\plugins\generic\googleFonts\classes\GoogleFont;
use APP\plugins\generic\googleFonts\exceptions\GoogleFontsPluginException;
use APP\template\TemplateManager;
use Exception;
use Illuminate\Support\Collection;
use PKP\core\Core;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use stdClass;

class GoogleFontsPlugin extends GenericPlugin
{
    public const FONTS_FILE = 'fonts/fonts.json';
    public const FONTS_PUBLIC_FILE_DIR = 'google-fonts';
    public const FONTS_SETTING = 'fonts';

    public function getDisplayName()
    {
        return __('plugins.generic.googleFonts.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.googleFonts.description');
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        Hook::add('Template::Settings::website::appearance', [$this, 'addSettingsTab']);
        Hook::add('Template::Settings::admin::appearance', [$this, 'addSettingsTab']);
        Hook::add('TemplateManager::display', [$this, 'addSettingsStyles']);
        Hook::add('LoadHandler', [$this, 'addSettingsHandler']);
        Hook::add('TemplateManager::display', fn(string $hookName, array $args) => $this->addFontStyle($args[0]));
        Hook::add('ArticleHandler::download', function(string $hookName, array $args) {
            $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
            $this->addFontStyle($templateMgr);
        });

        return true;
    }

    /**
     * Add settings link in plugins list
     *
     * This link redirects to the settings tab at
     * Settings > Website > Appearance > Google Fonts.
     */
    public function getActions($request, $verb)
    {
        if (!$this->getEnabled()) {
            return parent::getActions($request, $verb);
        }

        return array_merge(
            [
                new LinkAction(
                    'settings',
                    new RedirectAction(
                        $request->getDispatcher()->url(
                            $request,
                            Application::ROUTE_PAGE,
                            null,
                            'management',
                            'settings',
                            ['website'],
                            null,
                            'appearance/google-fonts'
                        ),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ],
            parent::getActions($request, $verb)
        );
    }

    /**
     * Add settings tab to edit Google Font settings
     *
     * Adds tab to: Settings > Website > Appearance > Google Fonts.
     */
    public function addSettingsTab(string $hookName, array $args): bool
    {
        $output = &$args[2];
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        try {
            $options = $this->loadJsonFile(self::FONTS_FILE);
        } catch (Exception $e) {
            $options = [];
            $error = __('plugins.generic.googleFonts.technicalError', ['error' => $e->getMessage()]);
        }

        $templateMgr->addStyleSheet(
            'google-fonts-settings',
            "{$this->getPluginUrl()}/styles/settings.css",
        );

        $templateMgr->assign([
            'googleFontsEnabled' => $this->getEnabledFonts()->values()->all(),
            'googleFontsError' => $error ?? '',
            'googleFontsOptions' => $options,
        ]);

        $template = $templateMgr->fetch($this->getTemplateResource("settings.tpl"));

        $output = $template;

        return false;
    }

    /**
     * Add the stylesheet for the settings tab
     */
    public function addSettingsStyles(string $hookName, array $args): bool
    {
        /** @var TemplateManager */
        $templateMgr = $args[0];
        $templateMgr->addStyleSheet(
            'google-fonts-settings',
            $this->getPluginUrl() . '/styles/settings.css',
            [
                'contexts' => ['backend-management'],
            ]
        );

        return false;
    }

    /**
     * Load a custom Handler to edit plugin settings forms
     */
    public function addSettingsHandler(string $hookName, array $args): bool
    {
        $page = $args[0];
        $handler = & $args[3];

        if ($this->getEnabled() && $page === 'google-font') {
            $handler = new GoogleFontsHandler($this);
            return true;
        }
        return false;
    }

    /**
     * Add font <style> tag to theme frontend pages
     *
     * Adds the @font-face definitions to all frontend pages.
     * The <style> attribute is attached to the {load_stylesheet}
     * template tag. Themes must use the following in their
     * template files.
     *
     * {load_stylesheet context="frontend"}
     */
    protected function addFontStyle(TemplateManager $templateMgr): bool
    {
        $enabledFonts = $this->getEnabledFonts();

        if (!$enabledFonts->count()) {
            return false;
        }

        try {
            $fontfaces = $this->getFontFaces($enabledFonts);
        } catch (GoogleFontsPluginException $e) {
            /**
             * TODO: Log the error somewhere
             *
             * A failed font load shouldn't crash the site, so
             * we catch the exception and fail silently.
             *
             * Ideally, we would log the error somewhere so
             * that it can be surfaced in the admin area.
             */
            return false;
        }

        $templateMgr->addStyleSheet(
            'google-fonts',
            $fontfaces,
            [
                'inline' => true,
                'contexts' => ['frontend', 'htmlGalley'],
            ]
        );

        return false;
    }

    /**
     * Load a JSON file in the plugin directory
     *
     * @param string $path Path to the file relative to this plugin's root directory
     * @throws GoogleFontsPluginException
     */
    public function loadJsonFile(string $path): mixed
    {
        $abspath = join('/', [
            Core::getBaseDir(),
            $this->getPluginPath(),
            $path,
        ]);

        $contents = file_get_contents($abspath);
        if (!$contents) {
            throw new GoogleFontsPluginException($this, "Unable to load the `{$path}` file.");
        }

        $decoded = json_decode($contents);
        if ($decoded === null) {
            throw new GoogleFontsPluginException($this, "Failed to decode `{$path}`. The file is not valid JSON.");
        }

        return $decoded;
    }

    /**
     * Get font details for a list of fonts
     *
     * Gets a subset of the full font options stored in self::FONTS_FILE.
     *
     * @param stdClass[] $fonts
     */
    public function getFonts(array $fonts, array $options): array
    {
        $matches = [];
        foreach ($options as $option) {
            if (in_array($option->id, $fonts)) {
                $matches[] = $option;
            }
        }
        return $matches;
    }

    /**
     * Get font details for a single font
     *
     * @see self::getFonts()
     */
    public function getFont(string $font, array $options): ?stdClass
    {
        $matches = $this->getFonts([$font], $options);
        if (count($matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Get the enabled fonts settings
     *
     * @return Collection<GoogleFont>
     */
    public function getEnabledFonts(?int $contextId = null): Collection
    {
        if (is_null($contextId)) {
            $contextId = Application::get()->getRequest()->getContext()?->getId() ?? Application::CONTEXT_ID_NONE;
        }
        $enabled = collect($this->getSetting($contextId, self::FONTS_SETTING) ?? []);

        return $enabled->map(fn(array $font) => new GoogleFont(
            id: $font['id'],
            family: $font['family'],
            category: $font['category'],
            subsets: $font['subsets'],
            variants: $font['variants'],
            lastModified: $font['lastModified'],
            version: $font['version'],
        ));
    }

    /**
     * Get @font-face definitions for enabled fonts
     *
     * @param Collection<GoogleFont> $fonts
     * @throws GoogleFontsPluginException
     */
    protected function getFontFaces(Collection $fonts): string
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $publicFileManager = new PublicFileManager();

        $basePath = join('/', [
            $request->getBaseUrl(),
            $context
                ? $publicFileManager->getContextFilesPath($context->getId())
                : $publicFileManager->getSiteFilesPath(),
            self::FONTS_PUBLIC_FILE_DIR,
        ]);

        $output = $fonts->map(function(GoogleFont $font) use ($basePath) {
            try {
                $embeds = $this->loadJsonFile("fonts/{$font->id}/embed.json");
            } catch (GoogleFontsPluginException $e) {
                throw $e;
            }
            $embedStatements = [];
            foreach ($embeds as $embed) {
                $embedStatements[] = "/* {$embed->subset} */";
                $embedStatements[] = str_replace(
                    './fonts',
                    $basePath,
                    $embed->font,
                );
            }
            return $embedStatements;
        });


        return join("\n", $output->flatten()->toArray());
    }

    /**
     * Get the URL to the plugin's root directory
     */
    protected function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }
}
