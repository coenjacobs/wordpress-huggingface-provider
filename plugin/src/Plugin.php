<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider;

use CoenJacobs\HuggingFaceProvider\Admin\SettingsPage;
use CoenJacobs\HuggingFaceProvider\Http\WpHttpClient;
use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceProvider;
use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceSettings;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\HttpTransporter;

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setup(): void
    {
        add_action('init', [$this, 'registerProvider'], 5);

        if (is_admin()) {
            $settings_page = new SettingsPage();
            add_action('admin_menu', [$settings_page, 'registerMenu']);

            $settings = new HuggingFaceSettings();
            add_action('admin_init', [$settings, 'registerSettings']);
        }
    }

    /**
     * Register the Hugging Face provider with the WordPress AI Client registry.
     */
    public function registerProvider(): void
    {
        if (!class_exists(AiClient::class)) {
            return;
        }

        $registry = AiClient::defaultRegistry();

        if ($registry->hasProvider(HuggingFaceProvider::class)) {
            return;
        }

        $registry->registerProvider(HuggingFaceProvider::class);

        $api_key = HuggingFaceSettings::getActiveApiKey();
        if (!empty($api_key)) {
            $auth = new ApiKeyRequestAuthentication($api_key);
            $registry->setProviderRequestAuthentication('huggingface', $auth);
        }

        // Set up the HTTP transporter if not already configured.
        // This is needed for actual model execution during AI Experiments.
        // Only works when AI Experiments plugin is installed (provides unscoped PSR interfaces).
        try {
            $registry->getHttpTransporter();
        } catch (\Throwable $e) {
            if (class_exists('Nyholm\\Psr7\\Factory\\Psr17Factory')) {
                $factory     = new \Nyholm\Psr7\Factory\Psr17Factory();
                $client      = new WpHttpClient();
                $transporter = new HttpTransporter($client, $factory, $factory);
                $registry->setHttpTransporter($transporter);
            }
        }
    }
}
