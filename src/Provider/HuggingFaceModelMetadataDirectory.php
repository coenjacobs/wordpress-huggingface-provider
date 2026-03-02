<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider;

use CoenJacobs\HuggingFaceProvider\Dependencies\CoenJacobs\WordPressAiProvider\ModelDirectory\AbstractModelMetadataDirectory;

class HuggingFaceModelMetadataDirectory extends AbstractModelMetadataDirectory
{
    protected function getModelsApiUrl(): string
    {
        return HuggingFaceProvider::apiBaseUrl() . '/models';
    }

    /**
     * @param array<string, mixed> $rawModel
     * @return array<string, mixed>|null
     */
    protected function parseModelEntry(array $rawModel): ?array
    {
        $modelId = substr($rawModel['id'], 0, 200);

        return [
            'id' => $modelId,
            'name' => $modelId,
            'provider' => self::extractProviderFromId($modelId),
        ];
    }

    /**
     * Extract the owner prefix from a Hugging Face model ID.
     *
     * Hugging Face model IDs follow the format "owner/model-name".
     */
    public static function extractProviderFromId(string $modelId): string
    {
        $slashPos = strpos($modelId, '/');
        if ($slashPos === false) {
            return 'Other';
        }

        return substr($modelId, 0, $slashPos);
    }
}
