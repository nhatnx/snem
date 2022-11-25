<?php

namespace app\core;

class Model
{
    private static array $_models = [];
    public $name = null;

    public function __construct()
    {
        $this->name = get_class($this);
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        $methodName = 'get' . ucfirst($property);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return $this;
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        $methodName = 'set' . ucfirst($property);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return $this;
    }

    /**
     * Returns the static model of the specified class.
     *
     * @param string $className
     *
     * @return Model the static model class
     */
    public static function model($className = __CLASS__)
    {
        if (!array_key_exists($className, self::$_models)) {
            self::$_models[$className] = new $className();
        }

        return self::$_models[$className];
    }

    /**
     * Set attributes
     *
     * @param array $attributes $attributes[$attributeKey => $attributeValue]
     */
    public function setAttributes($attributes)
    {
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function getModelName()
    {
        return $this->name;
    }
}
