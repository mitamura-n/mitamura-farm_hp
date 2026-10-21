<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_prefecture_has_exactly_one_quote_rate_matching_the_shipping_table(): void
    {
        $all=[];
        foreach (config('shipping.regions') as $region) {
            foreach ($region['prefectures'] as $prefecture) {
                $this->assertArrayNotHasKey($prefecture,$all);
                $all[$prefecture]=$region['standard'][100];
            }
        }
        $this->assertCount(47,$all);
        $this->assertEqualsCanonicalizing(config('farm.prefectures'),array_keys($all));
        $response=$this->get('/')->assertOk();
        preg_match('/<script id="shipping-rates"[^>]*>(.*?)<\/script>/s',$response->getContent(),$matches);
        $this->assertSame($all,json_decode($matches[1],true));
        $shipping=$this->get('/shipping')->assertOk();
        $document=new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$shipping->getContent());
        $xpath=new \DOMXPath($document);
        $this->assertSame(1,$xpath->query('//details[@open]/summary[contains(.,"地域別の送料表")]')->length);
    }
}
