## Rain Halcyon

Halcyon is a file based ORM, and the cousin of Eloquent. The goal of this library is to create a solution for file based object storage that shares the same API as database stored models.

### Registering a data source

Datasources reside inside a resolving container called `October\Rain\Halcyon\Datasource\Resolver`. The following datasources are supported:

- `October\Rain\Halcyon\Datasource\FileDatasource`: File based datasource.

Here is an example of registering a datasource called `theme1`, then binding the resolver to all models.

    use October\Rain\Halcyon\Model;
    use October\Rain\Filesystem\Filesystem;
    use October\Rain\Halcyon\Datasource\FileDatasource;
    use October\Rain\Halcyon\Datasource\Resolver;

    $datasource = new FileDatasource('/path/to/theme', new Filesystem);
    $resolver = new Resolver(['theme1' => $datasource]);
    $resolver->setDefaultDatasource('theme1');
    Model::setDatasourceResolver($resolver);

### Model example

Inherit the `October\Rain\Halcyon\Model` to create a new model:

    <?php

    use October\Rain\Halcyon\Model;

    class MyPage extends Model
    {
        /**
         * @var array The attributes that are mass assignable.
         */
        protected $fillable = [
            'markup',
            'title',
        ];

        /**
         * @var string The container name associated with the model, eg: pages.
         */
        protected $dirName = 'pages';
    }

The following attributes are reserved and have baked in functionality:

- **fileName**: Reserved for the template file name.
- **content**: Reserved for the complete file contents.
- **settings**: Stores the template INI settings.
- **markup**: Stores the template HTML markup.
- **code**: Stores the template PHP code (optional).
- **mtime**: Last modified time.

Now we are free to create a new page:

    MyPage::create([
        'fileName' => 'my-file',
        'title' => 'Test page',
        'markup' => '<p>Hello world!</p>'
    ]);

Executing the above code will create a new file **/path/to/theme/pages/my-file.htm**, with the following contents:

    title = "Test page"
    ==
    <p>Hello world!</p>

We can find the page and use it later:

    $page = MyPage::find('my-file');
    echo '<h1>'.$page->title.'</h1>';
    echo $page->markup;

If we change the file name, it will be renamed on the file system too:

    // New file path: /path/to/theme/pages/renamed-file.htm
    $page->fileName = 'renamed-file';
    $page->save();
