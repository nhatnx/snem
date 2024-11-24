<?php

namespace app\core;

use ADODB_Active_Record;

class SEActiveRecordModel extends ADODB_Active_Record
{
    private static array $_models = [];

    public const SEAR_RELATION_BELONGS_TO = 1;
    public const SEAR_RELATION_HAS_MANY = 2;

    public ?array $primaryKeys = ['id'];
    public ?string $_name = null;
    public string $createdAt = 'created_at';
    public string $updatedAt = 'updated_at';

    public function __construct($parent = null)
    {
        if ($parent) {
            if (is_string($parent)) {
                $tableName = $parent;
            } else {
                $tableName = $parent->name ?? '';
            }
        } else {
            $tableName = static::getTableName();
        }

        if (empty($tableName)) {
            $tableName = get_class($this);
        }

        parent::__construct($tableName, array_map('strtoupper', $this->primaryKeys));

        self::useDefaultValues(true);
        self::$_changeNames = false;

        $this->_name = get_class($this);

        foreach ($this->relations() as $relation) {
            $relationType = $relation[0];
            $foreignKey = $relation[1];
            $modelName = $relation[2];

            $foreignRef = $modelName::getTableName();

            if ($relationType == static::SEAR_RELATION_BELONGS_TO) {
                $this->belongsTo($foreignRef, $foreignKey);
            } elseif ($relationType == static::SEAR_RELATION_HAS_MANY) {
                $this->hasMany($foreignRef, $foreignKey);
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->relations()[$name])) {
            $relation = $this->relations()[$name];
            $relationType = $relation[0];
            $foreignKey = $relation[1];
            $modelName = $relation[2];

            $return = null;
            if ($relationType == static::SEAR_RELATION_BELONGS_TO) {
                if (!empty($this->$foreignKey)) {
                    if ($k = reset($modelName::model()->primaryKeys)) {
                        $relationId = $k;
                    } else {
                        $relationId = 'id';
                    }

                    $return = $modelName::model()->find("{$relationId} = {$this->$foreignKey}")[0] ?? null;
                }
            } elseif ($relationType == static::SEAR_RELATION_HAS_MANY) {
                if ($k = reset($this->primaryKeys)) {
                    $relationId = $k;
                } else {
                    $relationId = 'id';
                }

                $return = $modelName::model()->find("{$foreignKey} = {$this->$relationId}");
            }

            return $return;
        }

        return parent::__get($name);
    }

    public static function getTableName(): string
    {
        return '';
    }

    /**
     * ['foreignName' => ['SEAR_RELATION_BELONGS_TO' || 'SEAR_RELATION_HAS_MANY', foreignKey, foreignTableName]]
     * @return array
     */
    public function relations(): array
    {
        return [];
    }

    /**
     * Returns the static model of the specified class.
     *
     * @param string $className
     *
     * @return SEActiveRecordModel the static model class
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

    public function getModelName(): ?string
    {
        return $this->_name;
    }

    public function isNewRecord(): bool
    {
        return $this->_saved === false;
    }

    public function beforeSave()
    {
        $createdAt = $this->createdAt;
        $updatedAt = $this->updatedAt;

        if (property_exists($this, $updatedAt) &&
            (empty($this->$updatedAt) || $this->$updatedAt === 'CURRENT_TIMESTAMP')
        ) {
            $this->$updatedAt = date('Y-m-d H:i:s');
        }

        if ($this->isNewRecord() && property_exists($this, $createdAt) &&
            (empty($this->$createdAt) || $this->$createdAt === 'CURRENT_TIMESTAMP')
        ) {
            $this->$createdAt = date('Y-m-d H:i:s');
        }

        return true;
    }

    public function afterSave()
    {
        // Implement this method in a child class
    }

    public function save()
    {
        if ($this->beforeSave() && parent::save()) {
            $this->afterSave();

            return true;
        }

        return false;
    }
}
