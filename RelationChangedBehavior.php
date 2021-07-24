<?php
/**
 * @license MIT
 * @author denis909 <denis909@mail.ru>
 * @link https://denis909.spb.ru
 */
namespace denis909\yii;

use Closure;
use yii\db\ActiveRecord;

/**
 * la-haute-societe/yii2-save-relations-behavior is required
 */
class RelationChangedBehavior extends \yii\base\Behavior
{

    public $relations = [];

    public $event;

    public $onInsert = true;

    public $onUpdate = true;

    protected $oldRelations = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',   
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate'
        ];
    }

    public function beforeInsert($event)
    {
        if ($this->onInsert)
        {
            $this->saveOldRelations($event);
        }
    }

    public function afterInsert($event)
    {
        if ($this->onInsert)
        {
            $this->checkChangedRelations($event);
        }
    }

    public function beforeUpdate($event)
    {
        if ($this->onUpdate)
        {
            $this->saveOldRelations($event);
        }
    }

    public function afterUpdate($event)
    {
        if ($this->onUpdate)
        {
            $this->checkChangedRelations($event);
        }
    }

    protected function saveOldRelations($event)
    {
        $this->oldRelations = [];

        foreach($this->relations as $relation)
        {
            $oldRelation = $event->sender->getOldRelation($relation);

            if ($oldRelation)
            {
                if (is_array($oldRelation))
                {
                    foreach($oldRelation as $obj)
                    {
                        $this->oldRelations[$relation][] = $obj->primaryKey;
                    }
                }
                else
                {
                    $this->oldRelations[$relation][] = $oldRelation->primaryKey;
                }
            }
        }
    }

    protected function checkChangedRelations($event)
    {
        foreach($this->relations as $relation)
        {
            $oldValues = [];

            if (array_key_exists($relation, $this->oldRelations))
            {
                $oldValues = $this->oldRelations[$relation];
            }

            $value = $event->sender->$relation;

            if (is_array($value))
            {
                $currentValues = [];

                foreach($value as $obj)
                {
                    $currentValues[] = $obj->primaryKey;
                }

                foreach($oldValues as $id)
                {
                    if (array_search($id, $currentValues) === false)
                    {
                        $this->callEvent($relation, null, $id);
                    }
                }

                foreach($currentValues as $id)
                {
                    if (array_search($id, $oldValues) === false)
                    {
                        $this->callEvent($relation, $id, null);
                    }
                }
            }
            else
            {
                if (array_search($value, $oldValues[0]) === false)
                {
                    $this->callEvent($relation, $value, $oldValues[0]);
                }
            }
        }
    }

    protected function callEvent($relation, $value, $oldValue)
    {
        $e = new RelationChangedEvent;

        $e->sender = $this->owner;

        $e->relation = $relation;

        $e->oldValue = $oldValue;

        $e->value = $value;

        $event = $this->event;

        if ($event instanceof Closure)
        {
            $event($e);
        }
        else
        {
            if (is_callable($event))
            {
                call_user_func($event, $e);
            }
            else
            {
                $this->owner->trigger($event, $e);
            }
        }
    }

}