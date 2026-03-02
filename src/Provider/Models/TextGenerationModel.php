<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider\Models;

use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceProvider;
use CoenJacobs\HuggingFaceProvider\Provider\HuggingFaceSettings;
use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\Models\OpenAiCompatible\TextGenerationModel as BaseTextGenerationModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * Text generation model for Hugging Face.
 *
 * All Hugging Face Inference Provider models use the OpenAI-compatible /chat/completions endpoint.
 * Adds routing strategy suffix to model ID and optional X-HF-Bill-To header.
 */
class TextGenerationModel extends BaseTextGenerationModel
{
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        // Add organization billing header if configured.
        $organization = HuggingFaceSettings::getOrganization();
        if ($organization !== '') {
            $headers['X-HF-Bill-To'] = $organization;
        }

        return new Request($method, HuggingFaceProvider::url($path), $headers, $data, $this->getRequestOptions());
    }

    /**
     * @param \WordPress\AiClient\Messages\DTO\Message[] $prompt
     * @return array<string, mixed>
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        $params = parent::prepareGenerateTextParams($prompt);

        // Append routing strategy suffix to model ID.
        if (isset($params['model'])) {
            $strategy = HuggingFaceSettings::getRoutingStrategy();
            $params['model'] = $params['model'] . ':' . $strategy;
        }

        return $params;
    }
}
