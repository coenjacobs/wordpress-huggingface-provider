<?php

declare(strict_types=1);

namespace CoenJacobs\HuggingFaceProvider\Provider;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

class HuggingFaceProviderAvailability implements ProviderAvailabilityInterface
{
    public function isConfigured(): bool
    {
        return HuggingFaceSettings::getActiveApiKey() !== '';
    }
}
