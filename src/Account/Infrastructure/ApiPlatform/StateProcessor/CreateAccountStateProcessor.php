<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Infrastructure\ApiPlatform\Dto\CreateAccountDto;

class CreateAccountStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateAccountHandler $handler,
        private AccountRepositoryInterface $accountRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Account
    {
        // DEBUG: Log what we received
        error_log('=== CreateAccountStateProcessor DEBUG ===');
        error_log('Data type: ' . get_class($data));
        error_log('Data dump: ' . print_r($data, true));
        error_log('Context: ' . print_r($context, true));

        // Handle both DTO and direct Account entity
        if ($data instanceof CreateAccountDto) {
            error_log('Processing CreateAccountDto');
            $userId = $data->userId;
            $currency = $data->getCurrency();
        } elseif ($data instanceof Account) {
            error_log('Processing Account entity');
            // When deserialized directly to Account entity from request
            $userId = $data->getUserId();
            $currency = $data->getCurrency();
        } else {
            error_log('ERROR: Unknown data type received');
            throw new \InvalidArgumentException('Expected CreateAccountDto or Account entity, got: ' . get_class($data));
        }

        $command = new CreateAccountCommand($userId, $currency);

        $accountId = $this->handler->handle($command);

        return $this->accountRepository->findById($accountId);
    }
}