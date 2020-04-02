<?php

use Yami\Migration\AbstractMigration;

class TestClass extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.');
        $node->set(['foo' => 'baz']);

        $this->save();
    }

    public function down()
    {
        $node = $this->get('.');
        $node->remove('foo');

        $this->save();
    }

}