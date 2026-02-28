<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider;

use CoenJacobs\HuggingFaceProvider\Provider\Models\TextGenerationModel;
use RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class HuggingFaceProvider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        return 'https://router.huggingface.co/v1';
    }

    public static function apiBaseUrl(): string
    {
        return static::baseUrl();
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'huggingface',
            'Hugging Face',
            ProviderTypeEnum::cloud(),
            'https://huggingface.co/settings/tokens',
            RequestAuthenticationMethod::apiKey(),
        );
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new HuggingFaceProviderAvailability();
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new HuggingFaceModelMetadataDirectory();
    }

    /**
     * Create a text generation model for the given metadata.
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        foreach ($modelMetadata->getSupportedCapabilities() as $capability) {
            if ($capability->isTextGeneration()) {
                return new TextGenerationModel($modelMetadata, $providerMetadata);
            }
        }

        throw new RuntimeException(
            sprintf('No supported capabilities found for model "%s".', $modelMetadata->getId())
        );
    }
}
