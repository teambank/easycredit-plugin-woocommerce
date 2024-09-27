<?php
/**
 * Plan
 *
 * @category Class
 * @package  Teambank\RatenkaufByEasyCreditApiV3
 *
 * Transaction-V3 API Definition
 * @author   NETZKOLLEKTIV GmbH
 * @link     https://netzkollektiv.com

 */

namespace Teambank\RatenkaufByEasyCreditApiV3\Model;

use \ArrayAccess;
use \Teambank\RatenkaufByEasyCreditApiV3\ObjectSerializer;

/**
 * Plan Class Doc Comment
 *
 * @category Class
 * @package  Teambank\RatenkaufByEasyCreditApiV3
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null
 */
class Plan implements ModelInterface, ArrayAccess, \JsonSerializable
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'Plan';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'numberOfInstallments' => 'int',
        'installment' => 'float',
        'totalInterest' => 'float',
        'totalValue' => 'float',
        'lastInstallment' => 'float'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'numberOfInstallments' => null,
        'installment' => null,
        'totalInterest' => null,
        'totalValue' => null,
        'lastInstallment' => null
    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'numberOfInstallments' => 'numberOfInstallments',
        'installment' => 'installment',
        'totalInterest' => 'totalInterest',
        'totalValue' => 'totalValue',
        'lastInstallment' => 'lastInstallment'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'numberOfInstallments' => 'setNumberOfInstallments',
        'installment' => 'setInstallment',
        'totalInterest' => 'setTotalInterest',
        'totalValue' => 'setTotalValue',
        'lastInstallment' => 'setLastInstallment'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'numberOfInstallments' => 'getNumberOfInstallments',
        'installment' => 'getInstallment',
        'totalInterest' => 'getTotalInterest',
        'totalValue' => 'getTotalValue',
        'lastInstallment' => 'getLastInstallment'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }


    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['numberOfInstallments'] = $data['numberOfInstallments'] ?? null;
        $this->container['installment'] = $data['installment'] ?? null;
        $this->container['totalInterest'] = $data['totalInterest'] ?? null;
        $this->container['totalValue'] = $data['totalValue'] ?? null;
        $this->container['lastInstallment'] = $data['lastInstallment'] ?? null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets numberOfInstallments
     *
     * @return int|null
     */
    public function getNumberOfInstallments()
    {
        return $this->container['numberOfInstallments'];
    }

    /**
     * Sets numberOfInstallments
     *
     * @param int|null $numberOfInstallments Number of Installments defined in the payment plan ( = anzahl der Raten)
     *
     * @return self
     */
    public function setNumberOfInstallments($numberOfInstallments)
    {
        $this->container['numberOfInstallments'] = $numberOfInstallments;

        return $this;
    }

    /**
     * Gets installment
     *
     * @return float|null
     */
    public function getInstallment()
    {
        return $this->container['installment'];
    }

    /**
     * Sets installment
     *
     * @param float|null $installment installment
     *
     * @return self
     */
    public function setInstallment($installment)
    {
        $this->container['installment'] = $installment;

        return $this;
    }

    /**
     * Gets totalInterest
     *
     * @return float|null
     */
    public function getTotalInterest()
    {
        return $this->container['totalInterest'];
    }

    /**
     * Sets totalInterest
     *
     * @param float|null $totalInterest totalInterest
     *
     * @return self
     */
    public function setTotalInterest($totalInterest)
    {
        $this->container['totalInterest'] = $totalInterest;

        return $this;
    }

    /**
     * Gets totalValue
     *
     * @return float|null
     */
    public function getTotalValue()
    {
        return $this->container['totalValue'];
    }

    /**
     * Sets totalValue
     *
     * @param float|null $totalValue Amount of the total value in € ( = Gesamtsumme in €)
     *
     * @return self
     */
    public function setTotalValue($totalValue)
    {
        $this->container['totalValue'] = $totalValue;

        return $this;
    }

    /**
     * Gets lastInstallment
     *
     * @return float|null
     */
    public function getLastInstallment()
    {
        return $this->container['lastInstallment'];
    }

    /**
     * Sets lastInstallment
     *
     * @param float|null $lastInstallment lastInstallment
     *
     * @return self
     */
    public function setLastInstallment($lastInstallment)
    {
        $this->container['lastInstallment'] = $lastInstallment;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}


