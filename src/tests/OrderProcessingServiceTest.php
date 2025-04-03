<?php

namespace Tests\Unit;

use App\Order;
use App\APIResponse;
use App\DatabaseService;
use App\APIClient;
use App\OrderProcessingService;
use App\DatabaseException;
use App\APIException;
use PHPUnit\Framework\TestCase;

class OrderProcessingServiceTest extends TestCase
{
    /** @var DatabaseService&\PHPUnit\Framework\MockObject\MockObject */
    private $dbServiceMock;

    /** @var APIClient&\PHPUnit\Framework\MockObject\MockObject */
    private $apiClientMock;
    private $orderProcessingService;

    protected function setUp(): void
    {
        $this->dbServiceMock = $this->createMock(DatabaseService::class);
        $this->apiClientMock = $this->createMock(APIClient::class);
        $this->orderProcessingService = new OrderProcessingService($this->dbServiceMock, $this->apiClientMock);
    }

    public function testProcessOrder_TypeA_SuccessfulExport()
    {
        $order = new Order(1, 'A', 100, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);
        $this->dbServiceMock->expects($this->once())->method('updateOrderStatus')->with($order->id, 'exported', 'low');

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('exported', $orders[0]->status);
    }

    public function testProcessOrder_TypeA_FailedExport()
    {
        $order = new Order(2, 'A', 100, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);

        // Mock OrderProcessingService để ép `saveToCSV()` trả về false
        /** @var OrderProcessingService|\PHPUnit\Framework\MockObject\MockObject $orderProcessingMock */
        $orderProcessingMock = $this->getMockBuilder(OrderProcessingService::class)
            ->setConstructorArgs([$this->dbServiceMock, $this->apiClientMock])
            ->onlyMethods(['saveToCSV'])
            ->getMock();

        $orderProcessingMock->method('saveToCSV')->willReturn(false);

        $orders = $orderProcessingMock->processOrders(1);
        $this->assertEquals('export_failed', $orders[0]->status);
    }

    public function testProcessOrder_TypeB_API_Success()
    {
        $order = new Order(3, 'B', 80, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);
        $apiResponseMock = $this->createMock(APIResponse::class);
        $apiResponseMock->status = 'success';
        $apiResponseMock->data = 60;

        $this->apiClientMock->method('callAPI')->willReturn($apiResponseMock);

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('processed', $orders[0]->status);
    }

    public function testProcessOrder_TypeB_API_Failure()
    {
        $order = new Order(4, 'B', 80, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);
        $this->apiClientMock->method('callAPI')->willThrowException(new APIException());

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('api_failure', $orders[0]->status);
    }

    public function testProcessOrder_TypeC_Completed()
    {
        $order = new Order(5, 'C', 50, true);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('completed', $orders[0]->status);
    }

    public function testProcessOrder_TypeC_InProgress()
    {
        $order = new Order(6, 'C', 50, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('in_progress', $orders[0]->status);
    }

    public function testProcessOrder_HighPriority()
    {
        $order = new Order(7, 'B', 250, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);

        $this->dbServiceMock->expects($this->once())->method('updateOrderStatus')->with($order->id, $this->anything(), 'high');

        $this->orderProcessingService->processOrders(1);
    }

    public function testProcessOrder_DatabaseError()
    {
        $order = new Order(8, 'B', 50, false);
        $this->dbServiceMock->method('getOrdersByUser')->willReturn([$order]);
        $this->dbServiceMock->method('updateOrderStatus')->willThrowException(new DatabaseException());

        $orders = $this->orderProcessingService->processOrders(1);
        $this->assertEquals('db_error', $orders[0]->status);
    }

    public function testProcessOrder_HandlesException()
    {
        $this->dbServiceMock->method('getOrdersByUser')->willThrowException(new \Exception());

        $result = $this->orderProcessingService->processOrders(1);
        $this->assertFalse($result);
    }
}
