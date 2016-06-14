<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     MIT http://opensource.org/licenses/MIT
 */

namespace Mautic\Tests\Api;

class CampaignsTest extends MauticApiTestCase
{
    public function testGet()
    {
        echo "CampaignsTest - testGet on \n";

        $apiContext = $this->getContext('campaigns');
        $result     = $apiContext->get(1);

        echo "result => ".print_r($result, true);

        $message = isset($result['error']) ? $result['error']['message'] : '';
        $this->assertFalse(isset($result['error']), $message);
    }

//     public function testGetList()
//     {
//         $apiContext = $this->getContext('campaigns');
//         $result     = $apiContext->getList();

//         $message = isset($result['error']) ? $result['error']['message'] : '';
//         $this->assertFalse(isset($result['error']), $message);
//     }
}
