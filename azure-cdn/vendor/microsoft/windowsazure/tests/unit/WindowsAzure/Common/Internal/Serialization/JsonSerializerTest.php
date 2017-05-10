<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   Tests\Unit\WindowsAzure\Common\Internal\Serialization
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */

namespace Tests\Unit\WindowsAzure\Common\Internal\Serialization;
use Tests\Framework\TestResources;
use WindowsAzure\Common\Models\ServiceProperties;
use WindowsAzure\Common\Internal\InvalidArgumentTypeException;
use WindowsAzure\Common\Internal\Serialization\JsonSerializer;
use WindowsAzure\Common\Internal\Resources;


/**
 * Unit tests for class XmlSerializer
 *
 * @category  Microsoft
 * @package   Tests\Unit\WindowsAzure\Common\Internal\Serialization
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @version   Release: 0.4.2_2016-04
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */
class JsonSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::objectSerialize
     */
    public function testObjectSerialize()
    {
        // Setup
        $testData = TestResources::getSimpleJson();
        $rootName = 'testRoot';
        $expected = "{\"{$rootName}\":{$testData['jsonObject']}}";

        // Test
        $actual = JsonSerializer::objectSerialize($testData['dataObject'], $rootName);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::unserialize
     */
    public function testUnserializeArray()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = TestResources::getSimpleJson();
        $expected = $testData['dataArray'];

        // Test
        $actual = $jsonSerializer->unserialize($testData['jsonArray']);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::unserialize
     */
    public function testUnserializeObject()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = TestResources::getSimpleJson();
        $expected = $testData['dataObject'];

        // Test
        $actual = $jsonSerializer->unserialize($testData['jsonObject']);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::unserialize
     */
    public function testUnserializeEmptyString()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = "";
        $expected = null;

        // Test
        $actual = $jsonSerializer->unserialize($testData);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::unserialize
     */
    public function testUnserializeInvalidString()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = "{]{{test]";
        $expected = null;

        // Test
        $actual = $jsonSerializer->unserialize($testData);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::serialize
     */
    public function testSerialize()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = TestResources::getSimpleJson();
        $expected = $testData['jsonArray'];

        // Test
        $actual = $jsonSerializer->serialize($testData['dataArray']);

        // Assert
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers WindowsAzure\Common\Internal\Serialization\JsonSerializer::serialize
     */
    public function testSerializeNull()
    {
        // Setup
        $jsonSerializer = new JsonSerializer();
        $testData = null;
        $expected = "";
        $this->setExpectedException('WindowsAzure\Common\Internal\InvalidArgumentTypeException', sprintf(Resources::INVALID_PARAM_MSG, 'array', 'array'));

        // Test
        $actual = $jsonSerializer->serialize($testData);
    }
}
