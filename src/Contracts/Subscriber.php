<?php

namespace Marwa\Event\Contracts;

/**
 * Implementors declare their event subscriptions.
 *
 * Return format examples:
 * - [UserRegistered::class => 'onUserRegistered']
 * - [UserRegistered::class => [['onUserRegistered', 100], ['audit', 0]]]
 * - [OrderPlaced::class => [['sendMail', 10]]]
 */
interface Subscriber
{
    /**
     * @return array<string, string|array{0:string,1?:int}|array<int, array{0:string,1?:int}>>
     */
    public static function getSubscribedEvents(): array;
}
