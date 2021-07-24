<?php
/**
 * @license MIT
 * @author denis909 <denis909@mail.ru>
 * @link https://denis909.spb.ru
 */
namespace denis909\yii;

class RelationChangedEvent extends \yii\base\Event
{

    public $relation;

    public $value;

    public $oldValue;

}