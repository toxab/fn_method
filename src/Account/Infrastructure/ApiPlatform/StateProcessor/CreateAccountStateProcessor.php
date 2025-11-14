<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;

class CreateAccountStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateAccountHandler $handler,
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Account
    {
        // When deserialize: false, we need to manually get the request body
        $request = $context['request'] ?? null;

        if (!$request) {
            throw new \RuntimeException('Request not found in context');
        }

        // Get JSON payload
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['userId']) || !isset($payload['currency'])) {
            throw new \InvalidArgumentException('userId and currency are required');
        }

        $userId = $payload['userId'];
        $currency = Currency::from($payload['currency']);

        $command = new CreateAccountCommand($userId, $currency);

        $accountId = $this->handler->handle($command);

        return $this->accountRepository->findById($accountId);
    }
}