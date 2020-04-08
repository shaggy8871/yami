<?php

use Yami\Migration\AbstractMigration;

class AddWithInterimSync extends AbstractMigration
{
    public function up()
    {
        $node = $this->get('.foo');
        $node->add(['bar' => []]);

        $node = $this->get('.foo.bar');
        $node->add(['boo', 'buzz']);

        $this->save();
    }
}