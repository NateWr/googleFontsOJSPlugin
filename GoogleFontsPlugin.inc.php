<?php

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.googleFonts.exceptions.GoogleFontsPluginException');

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

        HookRegistry::register('Template::Settings::website::appearance', [$this, 'addSettingsTab']);
        HookRegistry::register('LoadHandler', [$this, 'addSettingsHandler']);

        return true;
    }

    /**
     * Add settings link in plugins list
     *
     * This link redirects to the settings tab at
     * Settings > Website > Appearance > Google Fonts.
     */
    public function getActions($request, $verb) {
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			$this->getEnabled()
                ? [
                    new LinkAction(
                        'settings',
                        new RedirectAction(
                            $request->getDispatcher()->url(
                                $request,
                                ROUTE_PAGE,
                                null,
                                'management',
                                'settings',
                                'website',
                                null,
                                'appearance/advanced'
                            ),
                            $this->getDisplayName()
                        ),
                        __('manager.plugins.settings'),
                        null
                    ),
                ]
                : [],
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
        $output =& $args[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context?->getId() ?? CONTEXT_ID_NONE;
        $templateMgr = TemplateManager::getManager($request);

        try {
            $options = $this->loadJsonFile(self::FONTS_FILE);
        } catch (Exception $e) {
            $options = [];
            $error = __('plugins.generic.googleFonts.technicalError', ['error' => $e->getMessage()]);
        }

        $enabledFonts = $this->getSetting($contextId, self::FONTS_SETTING) ?? [];

        $templateMgr->assign([
            'googleFontsEnabled' => $this->getFonts($enabledFonts, $options),
            'googleFontsError' => $error ?? '',
            'googleFontsOptions' => $options,
        ]);

        $template = $templateMgr->fetch($this->getTemplateResource("settings.tpl"));

        $output = $template;

        return true;
    }

    /**
     * Load a custom Handler to edit plugin settings forms
     */
    public function addSettingsHandler(string $hookName, array $args): bool
    {
		$page = $args[0];

		if ($this->getEnabled() && $page === 'google-font') {
			$this->import('pages/GoogleFontsHandler');
			define('HANDLER_CLASS', 'GoogleFontsHandler');
			return true;
		}
		return false;
    }

    /**
     * Load a JSON file in the plugin directory
     *
     * @param string $path Path to the file relative to this plugin's root directory
     * @throws Exception
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
     * Get font details for requested fonts
     *
     * Gets a subset of the full font options stored in self::FONTS_FILE.
     *
     * @param string[] $fonts The ID/dirname of the requested fonts
     */
    protected function getFonts(array $fonts, array $options): array
    {
        $matches = [];
        foreach ($options as $option) {
            if (in_array($option->id, $fonts)) {
                $matches[] = $option;
            }
        }
        return $matches;
    }
}
