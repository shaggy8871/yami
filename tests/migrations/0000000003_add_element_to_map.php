<?php

use Yami\Migration\AbstractMigration;

class AddElementToMap extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.foo');
        $node->add('element1');

        $this->save();
    }
}