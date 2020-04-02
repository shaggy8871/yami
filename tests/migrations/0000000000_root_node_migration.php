<?php

use Yami\Migration\AbstractMigration;

class RootNodeMigration extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.');
        $node->add(['foo' => 'baz']);

        $this->save();
    }

}