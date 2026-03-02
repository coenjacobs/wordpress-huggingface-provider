<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider;

use CoenJacobs\HuggingFaceProvider\Admin\SettingsPage;
use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceProvider;
use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceSettings;
use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\Provider\AbstractProviderPlugin;
use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\Provider\ProviderConfig;

class Plugin extends AbstractProviderPlugin
{
    /** @var static|null */
    protected static $instance = null;

    private static ProviderConfig $providerConfig;

    public static function providerConfig(): ProviderConfig
    {
        if (!isset(self::$providerConfig)) {
            self::$providerConfig = new ProviderConfig([
                'providerId' => 'huggingface',
                'providerName' => 'Hugging Face',
                'envVarName' => 'HUGGINGFACE_API_KEY',
                'constantName' => 'HUGGINGFACE_API_KEY',
                'enabledModelsOption' => 'huggingface_enabled_models',
                'modelsTransientKey' => 'huggingface_models_raw',
                'errorTransientKey' => 'huggingface_models_fetch_error',
                'refreshQueryParam' => 'huggingface_refresh_models',
                'refreshNonceAction' => 'huggingface_refresh_models',
                'pageSlug' => 'huggingface-provider',
                'optionGroup' => 'huggingface-provider',
                'sectionId' => 'huggingface',
                'sectionTitle' => 'Hugging Face',
                'sectionDescriptionHtml' => '<p>Get your API token from '
                    . '<a href="https://huggingface.co/settings/tokens" target="_blank"'
                    . ' rel="noopener noreferrer">huggingface.co/settings/tokens</a>.</p>',
                'pageTitle' => 'Hugging Face',
                'menuTitle' => 'Hugging Face',
                'infoCardTitle' => 'About Hugging Face',
                'infoCardDescription' => 'Hugging Face Inference Providers: unified API gateway'
                    . ' routing requests across multiple inference providers'
                    . ' with automatic failover.',
                'websiteUrl' => 'https://huggingface.co/settings/tokens',
                'websiteLinkText' => 'Get API Token',
            ]);
        }

        return self::$providerConfig;
    }

    protected function getConfig(): ProviderConfig
    {
        return self::providerConfig();
    }

    protected function getProviderClass(): string
    {
        return HuggingFaceProvider::class;
    }

    protected function createSettingsPage()
    {
        return new SettingsPage(self::providerConfig());
    }

    protected function createSettings()
    {
        return new HuggingFaceSettings(self::providerConfig());
    }
}
