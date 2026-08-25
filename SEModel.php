<?php

namespace app\core;

class SEModel
{
    private static array $_models = [];
    public ?string $name = null;

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

        return null;
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        $methodName = 'set' . ucfirst($property);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($value);
        }

        return $this;
    }

    /**
     * Returns the static model of the specified class.
     *
     * Defaults to the class the call was made on, which is why the default
     * cannot be __CLASS__ — that constant is bound at compile time to this
     * base class.
     *
     * @param string|null $className
     *
     * @return static the static model class
     */
    public static function model($className = null)
    {
        $className = $className ?? static::class;

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
    public function setAttributes($attributes): void
    {
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function getModelName(): ?string
    {
        return $this->name;
    }
}
