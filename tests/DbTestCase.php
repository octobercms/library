<?php

class DbTestCase extends TestCase
{
    public function setUp(): void
    {
        $this->db = new Illuminate\Database\Capsule\Manager;
        $this->db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        $this->db->setAsGlobal();
        $this->db->bootEloquent();
    }
}
