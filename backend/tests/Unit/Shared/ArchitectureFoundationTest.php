<?php

namespace Tests\Unit\Shared;

use App\Shared\Exceptions\ResourceNotFoundException;
use App\Shared\ValueObjects\OperationResult;
use App\Shared\ValueObjects\Pagination;
use PHPUnit\Framework\TestCase;

class ArchitectureFoundationTest extends TestCase
{
    public function test_pagination_calculates_offset(): void
    {
        $pagination = new Pagination(
            page: 3,
            perPage: 25,
        );

        $this->assertSame(
            50,
            $pagination->offset(),
        );
    }

    public function test_operation_result_supports_success(): void
    {
        $result = OperationResult::success(
            data: ['id' => '123'],
            message: 'Created.',
        );

        $this->assertTrue($result->successful);
        $this->assertSame(
            ['id' => '123'],
            $result->data,
        );
    }

    public function test_resource_not_found_exception_has_metadata(): void
    {
        $exception = new ResourceNotFoundException(
            'Business',
            'abc-123',
        );

        $this->assertSame(
            404,
            $exception->statusCode(),
        );

        $this->assertSame(
            'resource_not_found',
            $exception->errorCode(),
        );
    }
}
