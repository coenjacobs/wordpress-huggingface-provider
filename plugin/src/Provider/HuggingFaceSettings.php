<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider;

use CoenJacobs\HuggingFaceProvider\Admin\SettingsPage;

class HuggingFaceSettings
{
    public const PROVIDER_ID = 'huggingface';
    public const CREDENTIALS_OPTION = 'wp_ai_client_provider_credentials';

    /**
     * Check if the API key is configured via environment variable or PHP constant.
     */
    public static function hasEnvApiKey(): bool
    {
        $env = getenv('HUGGINGFACE_API_KEY');
        if (is_string($env) && $env !== '') {
            return true;
        }

        if (defined('HUGGINGFACE_API_KEY')) {
            $constant = constant('HUGGINGFACE_API_KEY');
            return is_string($constant) && $constant !== '';
        }

        return false;
    }

    /**
     * Get the active API key (ENV takes precedence over constant, constant over wp_options).
     */
    public static function getActiveApiKey(): string
    {
        $env = getenv('HUGGINGFACE_API_KEY');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        if (defined('HUGGINGFACE_API_KEY')) {
            $constant = constant('HUGGINGFACE_API_KEY');
            if (is_string($constant) && $constant !== '') {
                return $constant;
            }
        }

        $credentials = get_option(self::CREDENTIALS_OPTION, []);
        if (is_array($credentials) && isset($credentials[self::PROVIDER_ID])) {
            $key = $credentials[self::PROVIDER_ID];
            if (is_string($key)) {
                return $key;
            }
        }

        return '';
    }

    /**
     * Get the configured routing strategy.
     */
    public static function getRoutingStrategy(): string
    {
        $strategy = get_option('huggingface_routing_strategy', 'preferred');
        if (!is_string($strategy) || !in_array($strategy, ['preferred', 'fastest', 'cheapest'], true)) {
            return 'preferred';
        }

        return $strategy;
    }

    /**
     * Get the configured organization for billing.
     */
    public static function getOrganization(): string
    {
        $org = get_option('huggingface_organization', '');
        if (!is_string($org)) {
            return '';
        }

        return trim($org);
    }

    public function registerSettings(): void
    {
        $this->handleRefreshModels();

        register_setting(SettingsPage::OPTION_GROUP, self::CREDENTIALS_OPTION, [
            'type' => 'object',
            'default' => [],
            'sanitize_callback' => [$this, 'sanitizeCredentials'],
        ]);

        register_setting(SettingsPage::OPTION_GROUP, 'huggingface_enabled_models', [
            'type' => 'array',
            'default' => [],
            'sanitize_callback' => [$this, 'sanitizeEnabledModels'],
        ]);

        register_setting(SettingsPage::OPTION_GROUP, 'huggingface_routing_strategy', [
            'type' => 'string',
            'default' => 'preferred',
            'sanitize_callback' => [$this, 'sanitizeRoutingStrategy'],
        ]);

        register_setting(SettingsPage::OPTION_GROUP, 'huggingface_organization', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_section(
            'huggingface',
            'Hugging Face',
            [$this, 'renderSectionDescription'],
            SettingsPage::PAGE_SLUG
        );

        add_settings_field(
            'huggingface_api_key',
            'API Key',
            [$this, 'renderApiKeyField'],
            SettingsPage::PAGE_SLUG,
            'huggingface'
        );

        add_settings_field(
            'huggingface_routing_strategy',
            'Routing Strategy',
            [$this, 'renderRoutingStrategyField'],
            SettingsPage::PAGE_SLUG,
            'huggingface'
        );

        add_settings_field(
            'huggingface_organization',
            'Organization (Billing)',
            [$this, 'renderOrganizationField'],
            SettingsPage::PAGE_SLUG,
            'huggingface'
        );

        add_settings_field(
            'huggingface_enabled_models',
            'Enabled Models',
            [$this, 'renderModelField'],
            SettingsPage::PAGE_SLUG,
            'huggingface'
        );
    }

    public function renderSectionDescription(): void
    {
        echo '<p>Get your API token from <a href="https://huggingface.co/settings/tokens" target="_blank"'
            . ' rel="noopener noreferrer">huggingface.co/settings/tokens</a>.</p>';
    }

    /**
     * Render the API key settings field, showing env-configured key or an input.
     */
    public function renderApiKeyField(): void
    {
        if (self::hasEnvApiKey()) {
            $key = self::getActiveApiKey();
            $masked = strlen($key) > 8
                ? substr($key, 0, 3) . str_repeat('*', strlen($key) - 7) . substr($key, -4)
                : str_repeat('*', strlen($key));

            $source = getenv('HUGGINGFACE_API_KEY') !== false && getenv('HUGGINGFACE_API_KEY') !== ''
                ? 'HUGGINGFACE_API_KEY environment variable'
                : 'HUGGINGFACE_API_KEY constant';

            echo '<p>';
            echo '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> ';
            echo 'Configured via ' . esc_html($source);
            echo ' (<code>' . esc_html($masked) . '</code>)';
            echo '</p>';

            return;
        }

        $credentials = get_option(self::CREDENTIALS_OPTION, []);
        $value = $credentials[self::PROVIDER_ID] ?? '';
        echo '<input type="password" id="huggingface_api_key"'
            . ' name="' . esc_attr(self::CREDENTIALS_OPTION) . '[' . esc_attr(self::PROVIDER_ID) . ']"'
            . ' value="' . esc_attr($value) . '" class="regular-text" autocomplete="off" />';
    }

    /**
     * Render the routing strategy dropdown.
     */
    public function renderRoutingStrategyField(): void
    {
        $current = self::getRoutingStrategy();
        $options = [
            'preferred' => 'Preferred (default)',
            'fastest' => 'Fastest',
            'cheapest' => 'Cheapest',
        ];

        echo '<select name="huggingface_routing_strategy" id="huggingface_routing_strategy">';
        foreach ($options as $value => $label) {
            $selected = selected($current, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>'
                . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Controls how requests are routed across Hugging Face '
            . 'Inference Providers.</p>';
    }

    /**
     * Render the organization billing field.
     */
    public function renderOrganizationField(): void
    {
        $value = self::getOrganization();
        echo '<input type="text" id="huggingface_organization"'
            . ' name="huggingface_organization"'
            . ' value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Optional. When set, requests will include an '
            . '<code>X-HF-Bill-To</code> header to bill usage to this organization.</p>';
    }

    /**
     * Render the model selection checkboxes, grouped by owner prefix.
     */
    public function renderModelField(): void
    {
        $models = $this->fetchModels();
        $enabled = get_option('huggingface_enabled_models', []);
        if (!is_array($enabled)) {
            $enabled = [];
        }

        $fetchError = get_transient('huggingface_models_fetch_error');
        if (is_string($fetchError) && $fetchError !== '') {
            echo '<div class="notice notice-error inline"><p>'
                . 'Failed to fetch models: ' . esc_html($fetchError)
                . '</p></div>';
        }

        if (empty($models)) {
            echo '<p class="description">No models found. Try <strong>Refresh Model List</strong> below.</p>';
            return;
        }

        $modelIds = array_column($models, 'id');
        $staleModels = array_values(array_diff($enabled, $modelIds));

        $grouped = [];
        foreach ($models as $model) {
            $grouped[$model['provider']][] = $model;
        }
        ksort($grouped);

        $pluginFile = dirname(__DIR__, 2) . '/huggingface-provider.php';
        $pluginData = get_file_data($pluginFile, ['Version' => 'Version']);
        $version = $pluginData['Version'] ?: '0.1.0';

        wp_enqueue_script(
            'huggingface-model-selector',
            plugins_url('assets/model-selector.js', $pluginFile),
            [],
            $version,
            true
        );

        wp_enqueue_style(
            'huggingface-model-selector',
            plugins_url('assets/model-selector.css', $pluginFile),
            [],
            $version
        );

        echo '<div class="model-selector" data-default-collapsed="true" data-grouped="true"'
            . ' data-stale-models="' . esc_attr((string) wp_json_encode($staleModels)) . '">';
        echo '<input type="text" class="model-selector__search" placeholder="Search models..." />';
        echo '<div class="model-selector__chips"></div>';

        echo '<div class="model-selector__panel">';
        foreach ($grouped as $provider => $providerModels) {
            echo '<div class="model-selector__group" data-group="' . esc_attr($provider) . '">';
            echo '<button type="button" class="model-selector__group-header">';
            echo '<span class="model-selector__group-arrow">&#9656;</span>';
            echo '<span class="model-selector__group-name">' . esc_html($provider) . '</span>';
            echo '<span class="model-selector__group-count"></span>';
            echo '</button>';
            echo '<div class="model-selector__group-body">';
            foreach ($providerModels as $model) {
                $checked = in_array($model['id'], $enabled, true) ? ' checked' : '';
                echo '<label class="model-selector__item"'
                    . ' data-model-id="' . esc_attr($model['id']) . '"'
                    . ' data-model-name="' . esc_attr($model['name']) . '">';
                echo '<input type="checkbox" name="huggingface_enabled_models[]"'
                    . ' value="' . esc_attr($model['id']) . '"' . $checked . '>';
                echo '<span class="model-selector__item-label">' . esc_html($model['id']) . '</span>';
                echo '</label>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '<p class="model-selector__no-results">No models match your search.</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Fetch available models from the API via the model metadata directory.
     *
     * @return list<array{id: string, name: string, provider: string}>
     */
    private function fetchModels(): array
    {
        $directory = new HuggingFaceModelMetadataDirectory();
        return $directory->fetchAllModels();
    }

    /**
     * @param mixed $input
     * @return list<string>
     */
    public function sanitizeEnabledModels($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        return array_values(array_map('sanitize_text_field', $input));
    }

    /**
     * Sanitize the routing strategy option.
     *
     * @param mixed $input
     * @return string
     */
    public function sanitizeRoutingStrategy($input): string
    {
        if (!is_string($input) || !in_array($input, ['preferred', 'fastest', 'cheapest'], true)) {
            return 'preferred';
        }

        return $input;
    }

    /**
     * Sanitize the credentials option, merging our key into the shared array.
     *
     * @param array|mixed $input
     * @return array
     */
    public function sanitizeCredentials($input): array
    {
        $existing = get_option(self::CREDENTIALS_OPTION, []);
        if (!is_array($existing)) {
            $existing = [];
        }

        if (!is_array($input)) {
            return $existing;
        }

        $new_key = isset($input[self::PROVIDER_ID])
            ? trim($input[self::PROVIDER_ID])
            : ($existing[self::PROVIDER_ID] ?? '');

        $old_key = $existing[self::PROVIDER_ID] ?? '';
        if ($new_key !== $old_key) {
            delete_transient('huggingface_models_raw');
        }

        $existing[self::PROVIDER_ID] = $new_key;

        return $existing;
    }

    private function handleRefreshModels(): void
    {
        if (!isset($_GET['huggingface_refresh_models'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!check_admin_referer('huggingface_refresh_models')) {
            return;
        }

        delete_transient('huggingface_models_raw');

        wp_safe_redirect(admin_url('options-general.php?page=' . SettingsPage::PAGE_SLUG));
        exit;
    }
}
