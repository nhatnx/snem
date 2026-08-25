<?php

use app\core\SEActiveRecordModel;

class Post extends SEActiveRecordModel
{
    public ?array $primaryKeys = ['id'];

    public static function getTableName(): string
    {
        return 'st_posts';
    }

    /**
     * [name => [relation type, foreign key, related model]]
     *
     * Resolved lazily on property access: $post->author runs one query the
     * first time it is read.
     */
    public function relations(): array
    {
        return [
            'author' => [self::SEAR_RELATION_BELONGS_TO, 'author_id', User::class],
        ];
    }

    /**
     * beforeSave() in the base class fills created_at / updated_at; extend it
     * rather than replacing it.
     */
    public function beforeSave()
    {
        if (empty($this->title)) {
            return false;
        }

        return parent::beforeSave();
    }
}
