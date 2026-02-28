<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider;

use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\ModelDirectory\AbstractModelMetadataDirectory;
use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\Provider\AbstractProviderSettings;

class HuggingFaceSettings extends AbstractProviderSettings
{
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

    protected function registerAdditionalSettings(): void
    {
        $config = $this->getConfig();

        register_setting($config->getOptionGroup(), 'huggingface_routing_strategy', [
            'type' => 'string',
            'default' => 'preferred',
            'sanitize_callback' => [$this, 'sanitizeRoutingStrategy'],
        ]);

        register_setting($config->getOptionGroup(), 'huggingface_organization', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_field(
            'huggingface_routing_strategy',
            'Routing Strategy',
            [$this, 'renderRoutingStrategyField'],
            $config->getPageSlug(),
            $config->getSectionId()
        );

        add_settings_field(
            'huggingface_organization',
            'Organization (Billing)',
            [$this, 'renderOrganizationField'],
            $config->getPageSlug(),
            $config->getSectionId()
        );
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
     * Renders the model selection field for the settings page.
     */
    public function renderModelField(): void
    {
        $models = $this->fetchModels();
        $config = $this->getConfig();
        $enabled = get_option($config->getEnabledModelsOption(), []);
        if (!is_array($enabled)) {
            $enabled = [];
        }

        $fetchError = get_transient($config->getErrorTransientKey());
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
        $this->enqueueModelSelectorAssets($pluginFile);

        $enabledModelsOption = $config->getEnabledModelsOption();

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
                echo '<input type="checkbox" name="' . esc_attr($enabledModelsOption) . '[]"'
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

    protected function createModelMetadataDirectory(): AbstractModelMetadataDirectory
    {
        return new HuggingFaceModelMetadataDirectory($this->getConfig());
    }
}
