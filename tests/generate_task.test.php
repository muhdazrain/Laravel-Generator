<?php

require path('app') . '/tasks/generate.php';

class Generate_Test extends PHPUnit_Framework_TestCase
{
	public static $model;
	public static $controller;
	public static $migration;
	public static $view;


	public function setup()
	{
		// We don't care about the echos
		ob_start();

		self::$model = path('app') . '/models/book.php';
		self::$controller = path('app') . '/controllers/admin.php';
		self::$migration = path('app') . '/migrations/';
		self::$view = path('app') . 'views/';

		// Clear dirs
		File::cleandir(path('app') . 'controllers/');

		$this->generate = new Generate_Task;
	}


	// @group models
	public function test_can_create_model_file()
	{
		$this->generate->model('Book');

		$this->assertFileExists(self::$model);
	}


	// @group controllers
	public function test_can_create_controller_file()
	{
		$this->generate->controller(array(
			'Admin'
		));

		$this->assertFileExists(self::$controller);
	}


	public function test_can_add_actions()
	{
		$this->generate->controller(array(
			'Admin',
			'index',
			'show'
		));

		$contents = File::get(self::$controller);

		$this->assertContains('action_index', $contents);
		$this->assertContains('action_show', $contents);
	}


	public function test_controllers_can_be_restful()
	{
		$this->generate->controller(array(
			'admin',
			'index',
			'index:post',
			'update:put',
			'restful'
		));

		$contents = file::get(self::$controller);

		$this->assertContains('public $restful = true', $contents);
		$this->assertContains('get_index', $contents);
		$this->assertContains('post_index', $contents);
		$this->assertContains('put_update', $contents);
	}


	public function test_restful_can_be_any_argument()
	{
		$this->generate->controller(array(
			'admin',
			'restful',
			'index:post',
		));

		$contents = File::get(self::$controller);

		$this->assertContains('public $restful = true', $contents);
		$this->assertContains('post_index', $contents);
	}


	public function test_can_create_nested_controllers()
	{
		$this->generate->controller(array(
			'admin.panel'
		));

		$contents = File::get(path('app') . 'controllers/admin/panel.php');

		$this->assertContains('class Admin_Panel_Controller', $contents);
		$this->assertFileExists(path('app') . 'controllers/admin/panel.php');
	}


	// @group migrations
	public function test_can_create_migration_files()
	{
		$this->generate->migration(array(
			'create_users_table'
		));

		$file = File::latest(self::$migration);
		$this->assertFileExists((string)$file);
	}


	public function test_migration_offers_boilerplate_code()
	{
		$this->generate->migration(array(
			'create_users_table'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains('class Create_Users_Table', $contents);
		$this->assertContains('public function up', $contents);
		$this->assertContains('public function down', $contents);
	}


	public function test_migration_sets_up_create_schema()
	{
		$this->generate->migration(array(
			'create_users_table',
			'id:integer',
			'email:string'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains('Schema::create', $contents);
		$this->assertContains("\$table->increments('id')", $contents);
		$this->assertContains("\$table->string('email')", $contents);

		// Dropping too
		$this->assertContains("Schema::drop('users')", $contents);
	}


	public function test_migration_sets_up_add_schema()
	{
		$this->generate->migration(array(
			'add_user_id_to_posts_table',
			'user_id:integer'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains("Schema::table('posts'", $contents);
		$this->assertContains("\$table->integer('user_id')", $contents);
		$this->assertContains("\$table->drop_column('user_id')", $contents);
	}
	

	// @group views
	public function test_can_create_views()
	{
		$this->generate->view(array(
			'book',
			'test'
		));

		// Views default to blade
		$this->assertFileExists(self::$view . 'book.blade.php');
		$this->assertFileExists(self::$view . 'test.blade.php');
	}


	public function test_can_create_nested_views()
	{
		$this->generate->view(array(
			'book.index',
			'book.admin.show',
			'book'
		));

		$this->assertFileExists(self::$view . 'book/index.blade.php');
		$this->assertFileExists(self::$view . 'book/admin/show.blade.php');
		$this->assertFileExists(self::$view . 'book.blade.php');
	}


	// @group resource
	public function test_can_create_resources()
	{
		$this->generate->resource(array(
			'user',
			'index',
			'show'
		));

		$this->assertFileExists(self::$view . 'user/index.blade.php');
		$this->assertFileExists(self::$view . 'user/show.blade.php');

		$this->assertFileExists(path('app') . 'models/user.php');
		$this->assertFileExists(path('app') . 'controllers/users.php');
	}


	public function test_compensates_for_restful_declaration_when_creating_resources()
	{
		$this->generate->resource(array(
			'user',
			'index',
			'index:post',
			'restful'
		));

		$this->assertFileExists(self::$view . 'user/index.blade.php');
		$this->assertFileNotExists(self::$view . 'user/restful.blade.php');

		$contents = File::get(path('app') . 'controllers/users.php');

		$this->assertContains('public function get_index', (string)$contents);
		$this->assertContains('public function post_index', (string)$contents);
	}


	public function test_if_no_args_are_provided_it_will_generate_all_restful_methods()
	{
		$this->generate->resource(array('user'));

		// Should create the necessary views.
		$this->assertFileExists(self::$view . 'user/index.blade.php');
		$this->assertFileExists(self::$view . 'user/show.blade.php');
		$this->assertFileExists(self::$view . 'user/edit.blade.php');
		$this->assertFileExists(self::$view . 'user/new.blade.php');

		// Should create the necessary restful methods
		$contents = (string)File::get(path('app') . 'controllers/users.php');
		$this->assertContains('public $restful = true;', $contents);
		$this->assertContains('public function get_index', $contents);
		$this->assertContains('public function post_index', $contents);
		$this->assertContains('public function get_show', $contents);
		$this->assertContains('public function get_edit', $contents);
		$this->assertContains('public function get_new', $contents);
		$this->assertContains('public function put_update', $contents);
		$this->assertContains('public function delete_destroy', $contents);

		// Should create the model
		$this->assertFileExists(path('app') . 'models/user.php');
	}


	public function test_if_with_tests_is_provided_it_will_generate_tests()
	{
		$this->generate->resource(array('user', 'with_tests'));

		$this->assertFileExists(path('app') . 'tests/controllers/users.test.php');
		$contents = (string)File::get(path('app') . 'tests/controllers/users.test.php');
		

		$this->assertContains("\$response = Controller::call('Users@index');", $contents);
		$this->assertContains("\$this->assertEquals('200', \$response->foundation->getStatusCode());", $contents);
		$this->assertContains("\$this->assertRegExp('/.+/', (string)\$response, 'There should be some content in the index view.');", $contents);

		$this->assertNotContains("public function test_restful()", $contents);

	}

	// @group assets
	public function test_can_create_assets()
	{
		$this->generate->assets(array(
			'style1.css',
			'style2.css',
			'script1.js'
		));

		$css_path = path('public') . '/css';
		$js_path = path('public') . '/js';

		$this->assertFileExists("$css_path/style1.css");
		$this->assertFileExists("$css_path/style2.css");
		$this->assertFileExists("$js_path/script1.js");
	}


	public function test_can_create_nested_assets()
	{
		$this->generate->assets(array(
			'admin/style.css',
			'style3.css',
		));

		$css_path = path('public') . '/css';
		$js_path = path('public') . '/js';

		$this->assertFileExists("$css_path/style3.css");
		$this->assertFileExists("$css_path/admin/style.css");
	}


	public function test_can_fetch_common_assets()
	{
		$this->generate->assets(array(
			'jquery.js',
			'main.js'
			
		));

		$js_path = path('public') . 'js';

		$this->assertFileExists("$js_path/jquery.js");
		$this->assertFileExists("$js_path/main.js");

		$content = File::get("$js_path/jquery.js");
		$this->assertContains('jQuery JavaScript Library v1.8.1', $content);

		$content = File::get("$js_path/main.js");
		$this->assertEquals('', $content);
	}


	// @group test
	public function test_can_create_test_files()
	{
		$this->generate->test(array(
			'user',
			'can_disable_user',
			'can_reset_user_password'
		));

		$file = File::latest($this->generate->path('tests'));
		$this->assertFileExists((string)$file);

		$content = File::get($file);
		$this->assertContains('class User_Test extends PHPUnit_Framework_TestCase', $content);
		$this->assertContains('public function test_can_disable_user()', $content);
		$this->assertContains('public function test_can_reset_user_password()', $content);
	}


	public function tearDown()
	{
		ob_end_clean();

		File::delete(path('app') . 'controllers/admin.php');
		File::cleandir(path('app') . 'models');
		File::cleandir(path('app') . 'migrations');
		File::cleandir(path('public') . 'css');
		File::cleandir(path('public') . 'js');
		File::cleandir(path('app') . 'tests/controllers');
	}
}