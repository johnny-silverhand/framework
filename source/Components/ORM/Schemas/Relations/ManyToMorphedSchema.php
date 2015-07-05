<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\ORM\Schemas\Relations;

use Spiral\Components\DBAL\Schemas\AbstractTableSchema;
use Spiral\Components\ORM\ActiveRecord;
use Spiral\Components\ORM\ORMException;
use Spiral\Components\ORM\Schemas\MorphedRelationSchema;

class ManyToMorphedSchema extends MorphedRelationSchema
{
    /**
     * Relation type.
     */
    const RELATION_TYPE = ActiveRecord::MANY_TO_MORPHED;

    /**
     * Default definition parameters, will be filled if parameter skipped from definition by user.
     *
     * @invisible
     * @var array
     */
    protected $defaultDefinition = [
        ActiveRecord::PIVOT_TABLE       => '{name:singular}_map',
        ActiveRecord::INNER_KEY         => '{record:primaryKey}',
        ActiveRecord::OUTER_KEY         => '{outer:primaryKey}',
        ActiveRecord::THOUGHT_INNER_KEY => '{record:roleName}_{definition:INNER_KEY}',
        ActiveRecord::THOUGHT_OUTER_KEY => '{name:singular}_{definition:OUTER_KEY}',
        ActiveRecord::MORPH_KEY         => '{name:singular}_type',
        ActiveRecord::CONSTRAINT        => true,
        ActiveRecord::CONSTRAINT_ACTION => 'CASCADE',
        ActiveRecord::CREATE_PIVOT  => true,
        ActiveRecord::PIVOT_COLUMNS => [],
        ActiveRecord::WHERE_PIVOT   => [],
        ActiveRecord::WHERE         => []
    ];

    /**
     * Mount default values to relation definition.
     */
    protected function clarifyDefinition()
    {
        parent::clarifyDefinition();

        if ($this->isOuterDatabase())
        {
            throw new ORMException("Many-to-Many relation can not point to outer database data.");
        }
    }

    /**
     * Pivot table name.
     *
     * @return string
     */
    public function getPivotTableName()
    {
        return $this->definition[ActiveRecord::PIVOT_TABLE];
    }

    /**
     * Pivot table schema.
     *
     * @return AbstractTableSchema
     */
    public function getPivotSchema()
    {
        return $this->schemaBuilder->declareTable(
            $this->recordSchema->getDatabase(),
            $this->definition[ActiveRecord::PIVOT_TABLE]
        );
    }

    /**
     * Create all required relation columns, indexes and constraints.
     */
    public function buildSchema()
    {
        if (!$this->getOuterRecords() || !$this->definition[ActiveRecord::CREATE_PIVOT])
        {
            //No targets found, no need to generate anything
            return;
        }

        $pivotTable = $this->getPivotSchema();

        $localKey = $pivotTable->column($this->definition[ActiveRecord::THOUGHT_INNER_KEY]);
        $localKey->type($this->getInnerKeyType());
        $localKey->index();

        $morphKey = $pivotTable->column($this->getMorphKey());
        $morphKey->string(static::TYPE_COLUMN_SIZE);

        $outerKey = $pivotTable->column($this->definition[ActiveRecord::THOUGHT_OUTER_KEY]);
        $outerKey->type($this->getOuterKeyType());

        //Additional pivot columns
        foreach ($this->definition[ActiveRecord::PIVOT_COLUMNS] as $column => $definition)
        {
            $this->castColumn($pivotTable->column($column), $definition);
        }

        //Complex index
        $pivotTable->unique(
            $this->definition[ActiveRecord::THOUGHT_INNER_KEY],
            $this->definition[ActiveRecord::MORPH_KEY],
            $this->definition[ActiveRecord::THOUGHT_OUTER_KEY]
        );

        if ($this->definition[ActiveRecord::CONSTRAINT])
        {
            $foreignKey = $localKey->foreign(
                $this->recordSchema->getTable(),
                $this->recordSchema->getPrimaryKey()
            );

            $foreignKey->onDelete($this->definition[ActiveRecord::CONSTRAINT_ACTION]);
            $foreignKey->onUpdate($this->definition[ActiveRecord::CONSTRAINT_ACTION]);
        }
    }

    /**
     * Create reverted relations in outer model or models.
     *
     * @param string $name Relation name.
     * @param int    $type Back relation type, can be required some cases.
     * @throws ORMException
     */
    public function revertRelation($name, $type = null)
    {
        foreach ($this->getOuterRecords() as $record)
        {
            $record->addRelation($name, [
                ActiveRecord::MANY_TO_MANY      => $this->recordSchema->getClass(),
                ActiveRecord::PIVOT_TABLE       => $this->definition[ActiveRecord::PIVOT_TABLE],
                ActiveRecord::OUTER_KEY         => $this->definition[ActiveRecord::INNER_KEY],
                ActiveRecord::INNER_KEY         => $this->definition[ActiveRecord::OUTER_KEY],
                ActiveRecord::THOUGHT_INNER_KEY => $this->definition[ActiveRecord::THOUGHT_OUTER_KEY],
                ActiveRecord::THOUGHT_OUTER_KEY => $this->definition[ActiveRecord::THOUGHT_INNER_KEY],
                ActiveRecord::MORPH_KEY         => $this->definition[ActiveRecord::MORPH_KEY],
                ActiveRecord::CREATE_PIVOT  => $this->definition[ActiveRecord::CREATE_PIVOT],
                ActiveRecord::PIVOT_COLUMNS => $this->definition[ActiveRecord::PIVOT_COLUMNS],
                ActiveRecord::WHERE_PIVOT   => $this->definition[ActiveRecord::WHERE_PIVOT]
            ]);
        }
    }

    /**
     * Normalize relation options.
     *
     * @return array
     */
    protected function normalizeDefinition()
    {
        $definition = parent::normalizeDefinition();

        //Let's include pivot table columns
        $definition[ActiveRecord::PIVOT_COLUMNS] = [];
        foreach ($this->getPivotSchema()->getColumns() as $column)
        {
            $definition[ActiveRecord::PIVOT_COLUMNS][] = $column->getName();
        }

        return $definition;
    }
}