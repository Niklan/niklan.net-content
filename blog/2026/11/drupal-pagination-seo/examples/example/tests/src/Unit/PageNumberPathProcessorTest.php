<?php

declare(strict_types=1);

namespace Drupal\Tests\example\Unit;

use Drupal\example\Pager\PageNumberPathProcessor;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PageNumberPathProcessor::class)]
#[Group('example')]
final class PageNumberPathProcessorTest extends UnitTestCase {

  public function testInboundConvertsPageAndGuardsAgainstDoubleProcessing(): void {
    $request = Request::create('/example/pager?page=2');
    $processor = new PageNumberPathProcessor();

    $this->assertSame('/example/pager', $processor->processInbound('/example/pager', $request));
    $this->assertSame(1, $request->query->getInt('page'));
    $this->assertTrue($request->attributes->get('_page_number_processed'));

    $this->assertSame('/example/pager', $processor->processInbound('/example/pager', $request));
    $this->assertSame(1, $request->query->getInt('page'));
  }

  public function testInboundDoesNotTouchExcludedPaths(): void {
    $request = Request::create('/admin/content?page=2');
    $processor = new PageNumberPathProcessor();

    $processor->processInbound('/admin/content', $request);

    $this->assertSame(2, $request->query->getInt('page'));
    $this->assertFalse($request->attributes->has('_page_number_processed'));
  }

  public function testOutboundShiftsAndRemovesFirstPage(): void {
    $processor = new PageNumberPathProcessor();

    $options = ['query' => ['page' => 1]];
    $processor->processOutbound('/example/pager', $options);
    $this->assertSame(2, $options['query']['page']);

    $options = ['query' => ['page' => 0]];
    $processor->processOutbound('/example/pager', $options);
    $this->assertArrayNotHasKey('page', $options['query']);

    $options = ['query' => ['page' => -1]];
    $processor->processOutbound('/example/pager', $options);
    $this->assertSame(-1, $options['query']['page']);
  }

  public function testOutboundDoesNotTouchExcludedPaths(): void {
    $processor = new PageNumberPathProcessor();

    $options = ['query' => ['page' => 1]];
    $processor->processOutbound('/admin/content', $options);

    $this->assertSame(1, $options['query']['page']);
  }

}
