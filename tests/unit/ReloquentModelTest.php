<?php
use Carbon\Carbon;

class ReloquentModelTest extends Orchestra\Testbench\TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->bind('Reloquent\Contracts\ReloquentClientContract', function ($app) {
            return new Reloquent\Test\ReloquentClientMock();
        });
    }

    /**
     * @test
     */
    public function InstanceInitialization()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername'
        ]);

        $this->assertInstanceOf('Reloquent\Test\TestModel', $model);
    }

    /**
     * @test
     */
    public function CreateMethod()
    {
        $model = Reloquent\Test\TestModel::create([
            'id' => 1,
            'username' => 'testusername'
        ]);

        $this->assertInstanceOf('Reloquent\Test\TestModel', $model);
    }

    /**
     * @test
     */
    public function fillModelAttributes()
    {
        $model = new Reloquent\Test\TestModel();

        $attributes = [
            'id' => 1,
            'username' => 'testusername'
        ];

        $model->fill($attributes);

        $this->assertArrayHasKey('id', $model->attributesToArray());
        $this->assertArrayHasKey('username', $model->attributesToArray());
    }

    /**
     * @test
     */
    public function saveModel()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername'
        ]);

        $model->save();

        $this->assertTrue($model->exists());
    }

    /**
     * @test
     */
    public function deleteModel()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername'
        ]);

        $model->save();

        $this->assertEquals(1, $model->delete());
    }

    /**
     * @test
     */
    public function batchDeletion()
    {
        $ids = [1, 2, 3];
        $this->assertEquals(count($ids), Reloquent\Test\TestModel::destroy($ids));
    }

    /**
     * @test
     */
    public function findMethod()
    {
        $model = Reloquent\Test\TestModel::find(1);

        $this->assertInstanceOf('Reloquent\Test\TestModel', $model);
        $this->assertArrayHasKey('id', $model->attributesToArray());
    }

    /**
     * @test
     */
    public function updateTimestamps()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1
        ]);
        $model->save();
        $attributes = $model->attributesToArray();

        $this->assertArrayHasKey('id', $attributes);
        $this->assertInstanceOf('Carbon\Carbon',  new Carbon($attributes['created_at']));
    }

    /**
     * @test
     */
    public function setCreatedAt()
    {
        $model = new Reloquent\Test\TestModel();
        $model->setCreatedAt(Carbon::now()->format('Y-m-d g:i:s'));

        $attributes = $model->attributesToArray();
        $this->assertInstanceOf('Carbon\Carbon',  new Carbon($attributes['created_at']));
    }

    /**
     * @test
     */
    public function getFreshTimestamps()
    {
        $model = new Reloquent\Test\TestModel();
        $freshDate = $model->freshTimestamp();
        $this->assertInstanceOf('Carbon\Carbon',  new Carbon($freshDate));
    }

    /**
     *@test
     */
    public function getModelAttributesAsAray()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername'
        ]);

        $this->assertArrayHasKey('id', $model->attributesToArray());
        $this->assertArrayHasKey('username', $model->attributesToArray());
    }

    /**
     * @test
     */
    public function filterAttributesArray()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername',
            'password' => '$2y$10$Xa/ENLLNr16uqWaY5gWJUOJinlYs/A4cFzoMztegsidg/f3NZ/s1K'

        ]);
        $attributes = $model->attributesToArray();

        $this->assertFalse(in_array('password', $model->filterAttributes($attributes)));
    }

    /**
     * @test
     */
    public function jsonRepresentation()
    {
        $model = new Reloquent\Test\TestModel([
            'id' => 1,
            'username' => 'testusername',
        ]);

        $this->assertJson($model->toJson());
    }
}
