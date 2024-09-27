<?php
/**
 * TransactionUpdate
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
 * TransactionUpdate Class Doc Comment
 *
 * @category Class
 * @package  Teambank\RatenkaufByEasyCreditApiV3
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null
 */
class TransactionUpdate implements ModelInterface, ArrayAccess, \JsonSerializable
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'TransactionUpdate';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'orderValue' => 'float',
        'numberOfProductsInShoppingCart' => 'int',
        'orderId' => 'string',
        'shoppingCartInformation' => '\Teambank\RatenkaufByEasyCreditApiV3\Model\ShoppingCartInformationItem[]',
        'financingTerm' => 'int'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'orderValue' => null,
        'numberOfProductsInShoppingCart' => null,
        'orderId' => null,
        'shoppingCartInformation' => null,
        'financingTerm' => null
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
        'orderValue' => 'orderValue',
        'numberOfProductsInShoppingCart' => 'numberOfProductsInShoppingCart',
        'orderId' => 'orderId',
        'shoppingCartInformation' => 'shoppingCartInformation',
        'financingTerm' => 'financingTerm'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'orderValue' => 'setOrderValue',
        'numberOfProductsInShoppingCart' => 'setNumberOfProductsInShoppingCart',
        'orderId' => 'setOrderId',
        'shoppingCartInformation' => 'setShoppingCartInformation',
        'financingTerm' => 'setFinancingTerm'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'orderValue' => 'getOrderValue',
        'numberOfProductsInShoppingCart' => 'getNumberOfProductsInShoppingCart',
        'orderId' => 'getOrderId',
        'shoppingCartInformation' => 'getShoppingCartInformation',
        'financingTerm' => 'getFinancingTerm'
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
        $this->container['orderValue'] = $data['orderValue'] ?? null;
        $this->container['numberOfProductsInShoppingCart'] = $data['numberOfProductsInShoppingCart'] ?? null;
        $this->container['orderId'] = $data['orderId'] ?? null;
        $this->container['shoppingCartInformation'] = $data['shoppingCartInformation'] ?? null;
        $this->container['financingTerm'] = $data['financingTerm'] ?? null;
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
     * Gets orderValue
     *
     * @return float|null
     */
    public function getOrderValue()
    {
        return $this->container['orderValue'];
    }

    /**
     * Sets orderValue
     *
     * @param float|null $orderValue Amount in €
     *
     * @return self
     */
    public function setOrderValue($orderValue)
    {
        $this->container['orderValue'] = $orderValue;

        return $this;
    }

    /**
     * Gets numberOfProductsInShoppingCart
     *
     * @return int|null
     */
    public function getNumberOfProductsInShoppingCart()
    {
        return $this->container['numberOfProductsInShoppingCart'];
    }

    /**
     * Sets numberOfProductsInShoppingCart
     *
     * @param int|null $numberOfProductsInShoppingCart numberOfProductsInShoppingCart
     *
     * @return self
     */
    public function setNumberOfProductsInShoppingCart($numberOfProductsInShoppingCart)
    {
        $this->container['numberOfProductsInShoppingCart'] = $numberOfProductsInShoppingCart;

        return $this;
    }

    /**
     * Gets orderId
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->container['orderId'];
    }

    /**
     * Sets orderId
     *
     * @param string|null $orderId Shop transaction identifier (allows the shop to store its own reference for the transaction)
     *
     * @return self
     */
    public function setOrderId($orderId)
    {
        $this->container['orderId'] = $orderId;

        return $this;
    }

    /**
     * Gets shoppingCartInformation
     *
     * @return \Teambank\RatenkaufByEasyCreditApiV3\Model\ShoppingCartInformationItem[]|null
     */
    public function getShoppingCartInformation()
    {
        return $this->container['shoppingCartInformation'];
    }

    /**
     * Sets shoppingCartInformation
     *
     * @param \Teambank\RatenkaufByEasyCreditApiV3\Model\ShoppingCartInformationItem[]|null $shoppingCartInformation shoppingCartInformation
     *
     * @return self
     */
    public function setShoppingCartInformation($shoppingCartInformation)
    {
        $this->container['shoppingCartInformation'] = $shoppingCartInformation;

        return $this;
    }

    /**
     * Gets financingTerm
     *
     * @return int|null
     */
    public function getFinancingTerm()
    {
        return $this->container['financingTerm'];
    }

    /**
     * Sets financingTerm
     *
     * @param int|null $financingTerm ' Duration in months, depending on individual shop conditions and order value (please check your ratenkauf widget). Will be set to default value if not available. '
     *
     * @return self
     */
    public function setFinancingTerm($financingTerm)
    {
        $this->container['financingTerm'] = $financingTerm;

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


