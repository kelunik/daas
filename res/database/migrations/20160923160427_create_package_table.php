<?php

use Phinx\Migration\AbstractMigration;

class CreatePackageTable extends AbstractMigration  {
    public function change() {
        $table = $this->table("packages", [
            "primary_key" => ["vendor", "name"],
            "id" => false,
        ]);

        $table->addColumn("vendor", "string", ["limit" => 128])
            ->addColumn("name", "string", ["limit" => 128])
            ->addColumn("platform", "string", ["limit" => 16])
            ->addColumn("href", "string", ["limit" => 1024])
            ->create();
    }
}
