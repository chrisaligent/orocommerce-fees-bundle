<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddProcessingFeeToOrders implements
    Migration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addProcessingFeeToOrder($schema);
    }

    /**
     * @throws SchemaException
     */
    protected function addProcessingFeeToOrder(Schema $schema): void
    {
        $table = $schema->getTable('oro_order');

        if (!$table->hasColumn('processing_fee')) {
            $table->addColumn(
                'processing_fee',
                'money',
                [
                    'notnull' => false,
                    'precision' => 19,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                    'oro_options' => [
                        'extend' => ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM],
                        'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                        'form' => [
                            'is_enabled' => true
                        ],
                        'view' => [
                            'is_displayable' => false,
                        ]
                    ],
                ]
            );
        }
    }
}
