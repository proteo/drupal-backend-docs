<?php

namespace Drupal\cache_webhook;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Site\Settings;

/**
 * Runs on the Drupal site, not in Laravel. Tag the service `needs_destruction`.
 */
final class FrontendFlushSender implements DestructableInterface
{
    // #region sender
    protected array $changes = [];

    protected function scheduleFrontendFlush(EntityInterface $entity, string $operation): void
    {
        $this->changes[] = [
            'event' => $entity->getEntityTypeId().'.'.$operation,
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->bundle(),
            'uuid' => $entity->uuid(),
            'nonce' => Crypt::randomBytesBase64(16),
        ];
    }

    public function destruct(): void
    {
        if (! $this->changes) {
            return;
        }
        $body = Json::encode(['changes' => $this->changes]);
        $this->changes = [];
        $secret = (string) Settings::get('cache_webhook.secret', false);
        $url = Settings::get('cache_webhook.url', false);
        if (! $secret || ! $url) {
            return;
        }
        $timestamp = (string) $this->time->getCurrentTime();
        try {
            $this->httpClient->request('POST', $url, [
                // Must be the exact bytes that were signed.
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Drupal-Timestamp' => $timestamp,
                    'X-Drupal-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, $secret),
                ],
                'timeout' => 5,
            ]);
        } catch (\Throwable $e) {
            // Never break content editing because the frontend is unreachable.
            $this->logger->error('Frontend cache flush failed: @message', ['@message' => $e->getMessage()]);
        }
    }
    // #endregion sender
}
