<?php

use Phinx\Migration\AbstractMigration;

class CreateVersionTable extends AbstractMigration {
    public function change() {
        $table = $this->table("versions", [
            "primary_key" => ["vendor", "name", "version"],
            "id" => false,
        ]);

        $table->addColumn("vendor", "string", ["limit" => 128])
            ->addColumn("name", "string", ["limit" => 128])
            ->addColumn("version", "integer")
            ->addColumn("tag", "string", ["limit" => 32])
            ->create();
    }
}