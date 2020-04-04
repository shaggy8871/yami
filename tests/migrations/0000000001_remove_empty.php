<?php

use Yami\Migration\AbstractMigration;

class RemoveEmpty extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.bar');
        $node->remove('baz');

        $this->save();
    }
}