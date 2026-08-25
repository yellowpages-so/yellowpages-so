# Platform Architecture Normalization

Dependency direction:

Controller -> application service -> repository contract -> infrastructure.

Refactor one use case at a time.

For each use case:

1. Add DTO.
2. Add repository contract.
3. Add database repository.
4. Use TransactionManager.
5. Add domain exception.
6. Record audit event.
7. Preserve the existing HTTP contract.
8. Run focused and full tests.
