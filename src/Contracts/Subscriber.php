<?php

declare(strict_types=1);

namespace Marwa\Event\Contracts;

/**
 * Implementors declare their event subscriptions.
 *
 * Return format examples:
 * - [UserRegistered::class => 'onUserRegistered']
 * - [UserRegistered::class => [['onUserRegistered', 100], ['audit', 0]]]
 * - [OrderPlaced::class => [['sendMail', 10]]]
 *
 * @phpstan-type SubscriptionMap array<class-string, non-empty-string|array<int, mixed>>
 */
interface Subscriber
{
    /**
     * @return SubscriptionMap
     */
    public static function getSubscribedEvents(): array;
}
