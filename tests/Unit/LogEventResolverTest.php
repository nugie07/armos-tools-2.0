<?php

namespace Tests\Unit;

use App\Services\Log\LogEventResolver;
use App\Services\Log\LogReferenceExtractor;
use Tests\TestCase;

class LogEventResolverTest extends TestCase
{
    public function test_resolves_known_events(): void
    {
        $resolver = new LogEventResolver;
        $cases = [
            ['[ARMOS -> WMS] Syncing Inventory', 'syncing_inventory'],
            ['[ARMOS -> WMS] Synchronizing Order Manifest for DO x', 'synchronizing_order_manifest'],
            ['[ARMOS -> WMS] Synchronizing Route Manifest Generation #1', 'synchronizing_route_manifest_generation'],
            ['[ARMOS -> SQL] Patch Order Status to ok for Order ID 559495', 'patch_order_status_sql'],
            ['[ARMOS -> ATENA] Patch Order Status for route', 'patch_order_status_atena'],
            ['[ARMOS -> SQL] Picklist Route ABC', 'picklist_route'],
            ['[FEED ORDER V2 SQL -> TMS]', 'feed_order_v2_sql_tms'],
            ['[FEED ORDER V2 ATENA -> TMS]', 'feed_order_v2_atena_tms'],
            ['[WMS -> ARMOS] WEBHOOK_GOOD_ISSUE_RESULTS', 'webhook_good_issue_results'],
            ['Something unknown', 'other'],
            [null, 'other'],
        ];

        foreach ($cases as [$event, $expected]) {
            $this->assertSame($expected, $resolver->resolve($event), (string) $event);
        }
    }

    public function test_search_fields(): void
    {
        $resolver = new LogEventResolver;
        $this->assertNull($resolver->searchField('syncing_inventory'));
        $this->assertSame('faktur_reference_id', $resolver->searchField('patch_order_status_sql'));
        $this->assertSame('header.route_id', $resolver->searchField('picklist_route'));
    }
}

class LogReferenceExtractorTest extends TestCase
{
    public function test_simple_and_nested_fields(): void
    {
        $ex = new LogReferenceExtractor;

        $simple = $ex->extract([
            'faktur_reference_id' => 'B10SI2604-1346',
            'status' => 'ok',
        ], 'faktur_reference_id');
        $this->assertSame('B10SI2604-1346', $simple['value']);
        $this->assertFalse($simple['invalid_json']);

        $nested = $ex->extract([
            'header' => ['route_id' => 'RT-12345'],
        ], 'header.route_id');
        $this->assertSame('RT-12345', $nested['value']);

        $missing = $ex->extract(['a' => 1], 'faktur_reference_id');
        $this->assertNull($missing['value']);

        $nullField = $ex->extract(['faktur_reference_id' => null], 'faktur_reference_id');
        $this->assertNull($nullField['value']);

        $invalid = $ex->extract('{not-json', 'faktur_reference_id');
        $this->assertTrue($invalid['invalid_json']);
        $this->assertNull($invalid['value']);

        $noField = $ex->extract(['x' => 1], null);
        $this->assertNull($noField['value']);
        $this->assertFalse($noField['invalid_json']);
    }

    public function test_json_string_input(): void
    {
        $ex = new LogReferenceExtractor;
        $out = $ex->extract('{"outbound_reference":"C10SI2509-0041"}', 'outbound_reference');
        $this->assertSame('C10SI2509-0041', $out['value']);
    }
}
